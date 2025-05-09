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

class CartServiceTest extends TestCase
{
    private $cartService;
    private $sessionMock;
    private $productRepoMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $this->productRepoMock = $this->createMock(ProductRepository::class);
        $carrierRepoMock = $this->createMock(CarrierRepository::class);
        $orderDetailsRepoMock = $this->createMock(OrderDetailsRepository::class);
        $settingRepoMock = $this->createMock(SettingRepository::class);

        $requestStack->method('getSession')->willReturn($this->sessionMock);

        $this->cartService = new CartServices(
            $requestStack,
            $this->productRepoMock,
            $carrierRepoMock,
            $orderDetailsRepoMock, // ✅ bon ordre
            $settingRepoMock
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
}