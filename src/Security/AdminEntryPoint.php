<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AdminEntryPoint implements AuthenticationEntryPointInterface
{
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function start(Request $request, ?\Throwable $authException = null): RedirectResponse
    {
        // Récupère la session via RequestStack
        $session = $this->requestStack->getSession();
        if ($request->getPathInfo() === '/admin') {
            $session->set('redirect_from_admin', true);
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
