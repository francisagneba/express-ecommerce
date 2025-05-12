<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    public function testAddToCart(): void
    {
        $client = static::createClient();

        // ⚠️ À adapter selon un produit existant en base
        $productId = 143;

        // Simule une requête AJAX POST pour ajouter un produit au panier
        $client->xmlHttpRequest('POST', '/cart/add/' . $productId . '/2');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        // ✅ Vérifie que le panier contient bien des items
        $this->assertArrayHasKey('items', $data);
        $this->assertGreaterThan(0, count($data['items']));
        $this->assertEquals(2, $data['items'][0]['quantity']);
    }
}