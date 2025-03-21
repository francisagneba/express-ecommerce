<?php

namespace App\Services;

use App\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PaypalService
{

    private $session;
    private $paymentMethodRepo;

    public function __construct(
        RequestStack $requestStack,
        PaymentMethodRepository $paymentMethodRepo,
    ) {
        $this->session = $requestStack->getSession(); // Obtenir la session à partir de RequestStack
        $this->paymentMethodRepo = $paymentMethodRepo;
    }

    public function getPublicKey()
    {

        $config = $this->paymentMethodRepo->findOneByName('Paypal');
        if ($_ENV['APP_ENV'] === 'dev') {
            //On est en mode developpement
            return $config->getTestPublicApiKey();
        } else {
            //On est en mode production
            return $config->getProdPublicApiKey();
        }
    }

    public function getPrivateKey()
    {

        $config = $this->paymentMethodRepo->findOneByName('Paypal');
        if ($_ENV['APP_ENV'] === 'dev') {
            //On est en mode developpement
            return $config->getTestPrivateApiKey();
        } else {
            //On est en mode production
            return $config->getProdPrivateApiKey();
        }
    }

    public function getBaseUrl()
    {

        $config = $this->paymentMethodRepo->findOneByName('Paypal');
        if ($_ENV['APP_ENV'] === 'dev') {
            //On est en mode developpement
            return $config->getTestBaseUrl();
        } else {
            //On est en mode production
            return $config->getProdBaseUrl();
        }
    }
}
