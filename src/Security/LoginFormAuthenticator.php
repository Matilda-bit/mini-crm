<?php

namespace App\Security;

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Service\LoggerService;


class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private EntityManagerInterface $em;
    private LoggerService $loggerService;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(RouterInterface $router, EntityManagerInterface $em, LoggerService $loggerService)
    {
        $this->router = $router;
        $this->em = $em;
        $this->loggerService = $loggerService;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();

        if ($user instanceof User) {
            $user->setLoginTime(new \DateTime());
            $this->em->flush();
            $this->loggerService->logAction($user->getId(), 'login');
        }

        $role = $user->getRoles()[0];
        $routeMap = [
            'ROLE_ADMIN' => 'role_dashboard',
            'ROLE_REP' => 'role_dashboard',
            'ROLE_USER' => 'user_dashboard',
        ];
    
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        return new RedirectResponse($this->router->generate($routeMap[$role] ?? 'user_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->getFlashBag()->add('login_error', 'Invalid credentials.');

        return new RedirectResponse($this->router->generate('app_login_register'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
