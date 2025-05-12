<?php

namespace App\Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\CartServices;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;
use App\Repository\CarrierRepository;
use App\Repository\OrderDetailsRepository;
use App\Repository\SettingRepository;
use App\Entity\Product;
use App\Entity\Carrier;
use App\Entity\Setting;

class CartServiceTest extends TestCase
{
    private $cartService;
    private $sessionMock;
    private $productRepoMock;
    private $carrierRepoMock;
    private $orderDetailsRepoMock;
    private $settingRepoMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $this->productRepoMock = $this->createMock(ProductRepository::class);
        $this->carrierRepoMock = $this->createMock(CarrierRepository::class);
        $this->orderDetailsRepoMock = $this->createMock(OrderDetailsRepository::class);
        $this->settingRepoMock = $this->createMock(SettingRepository::class);

        $requestStack->method('getSession')->willReturn($this->sessionMock);

        $this->cartService = new CartServices(
            $requestStack,
            $this->productRepoMock,
            $this->carrierRepoMock,
            $this->orderDetailsRepoMock,
            $this->settingRepoMock
        );
    }



    private function configureSessionGetMock(array $cart = [], $carrier = null): void
    {
        $this->sessionMock->method('get')
            ->willReturnCallback(function ($key) use ($cart, $carrier) {
                return match ($key) {
                    'cart' => $cart,
                    'carrier' => $carrier,
                    default => null,
                };
            });
    }

    public function testAddToCartWhenProductExists(): void
    {
        $productId = 1;
        $mockProduct = $this->createMock(Product::class);

        $this->productRepoMock->method('find')->with($productId)->willReturn($mockProduct);

        // Simuler un panier vide et aucun transporteur
        $this->configureSessionGetMock([], null);

        $this->sessionMock->expects($this->exactly(2)) // au lieu de once()
            ->method('set')
            ->withConsecutive(
                ['cart', [$productId => 1]],
                [$this->equalTo('cartData'), $this->anything()]
            );

        $this->cartService->addToCart($productId);
    }

    public function testAddToCartThrowsExceptionWhenProductNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Product not found.");

        $this->productRepoMock->method('find')->willReturn(null);

        $this->cartService->addToCart(999);
    }

    public function testDeleteFromCartDecreasesQuantity(): void
    {
        $productId = 1;
        $initialCart = [$productId => 2];
        $expectedCart = [$productId => 1];

        $this->configureSessionGetMock($initialCart, null);

        $this->sessionMock->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['cart', $expectedCart],
                [$this->equalTo('cartData'), $this->anything()]
            );


        $this->cartService->deleteFromCart($productId);
    }

    public function testDeleteFromCartRemovesIfQuantityIsOne(): void
    {
        $productId = 1;
        $initialCart = [$productId => 1];
        $expectedCart = [];

        $this->configureSessionGetMock($initialCart, null);

        $this->sessionMock->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['cart', $expectedCart],
                [$this->equalTo('cartData'), $this->anything()]
            );

        $this->cartService->deleteFromCart($productId);
    }

    public function testGetFullCartReturnsEmptyStructure(): void
    {
        $this->sessionMock->method('get')->willReturn(null);

        $result = $this->cartService->getFullCart();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame([], $result['items']);
        $this->assertEquals([
            'subTotalHT' => 0,
            'subTotalTTC' => 0,
            'subTotalWithCarrier' => 0,
            'quantity' => 0,
            'carrier_id' => null,
            'carrier_name' => 'Aucun transporteur',
            'carrier_price' => 0,
            'taxe' => 0
        ], $result['data']);
    }

    public function testGetFullCartReturnsExpectedData(): void
    {
        $productId = 42;
        $quantity = 2;
        $productPrice = 100;
        $taxeRate = 20; // 20 %

        // 🧪 Simule un produit
        $mockProduct = $this->createMock(Product::class);
        $mockProduct->method('getId')->willReturn($productId);
        $mockProduct->method('getName')->willReturn('Produit Test');
        $mockProduct->method('getSlug')->willReturn('produit-test');
        $mockProduct->method('getImageUrls')->willReturn(['image.jpg']);
        $mockProduct->method('getSoldePrice')->willReturn((int) $productPrice); // ✅ cast en int
        $mockProduct->method('getRegularPrice')->willReturn($productPrice);

        $this->productRepoMock->method('find')->with($productId)->willReturn($mockProduct);

        // 🧪 Simule un transporteur
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->method('getId')->willReturn(5);
        $mockCarrier->method('getName')->willReturn('Chronopost');
        $mockCarrier->method('getDescription')->willReturn('Livraison rapide');
        $mockCarrier->method('getPrice')->willReturn(10);

        $this->carrierRepoMock
            ->method('findAll')
            ->willReturn([$mockCarrier]);

        // 🧪 Simule une taxe
        $mockSetting = $this->createMock(Setting::class);
        $mockSetting->method('getTaxeRate')->willReturn($taxeRate);
        $this->settingRepoMock->method('findOneBy')->willReturn($mockSetting);

        // 🧪 Simule la session avec un panier
        $this->sessionMock->method('get')->willReturnCallback(function ($key) use ($productId, $quantity) {
            return match ($key) {
                'cart' => [$productId => $quantity],
                'carrier' => null,
                default => null,
            };
        });

        // 🔍 Appelle la méthode
        $result = $this->cartService->getFullCart();

        // ✅ Vérifie le résultat attendu
        $this->assertEquals($quantity, $result['data']['quantity']);
        $this->assertEquals(200.0, $result['data']['subTotalTTC']);
        $this->assertEquals(10.0, $result['data']['carrier_price']);
        $this->assertEquals(210.0, $result['data']['subTotalWithCarrier']);
        $this->assertEquals(5, $result['data']['carrier_id']);
        $this->assertEquals('Chronopost', $result['data']['carrier_name']);
        $this->assertEquals(33, $result['data']['taxe']); // 200 TTC -> 166.67 HT * 0.2 = ~33.33
    }
}