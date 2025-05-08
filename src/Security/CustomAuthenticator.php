<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class CustomAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): bool
    {
        return $request->getPathInfo() === '/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');
        $csrfToken = $request->request->get('_csrf_token');

        if (null === $email) {
            throw new CustomUserMessageAuthenticationException('Email cannot be empty.');
        }

        // Special case for admin
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@innotravel.tn';
        $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'change_this_password';

        if ($email === $adminEmail && $password === $adminPassword) {
            $request->getSession()->set('admin', true);

            // For admin, we'll just set the session and redirect directly
            // This bypasses the need for a User object
            throw new CustomUserMessageAuthenticationException('Admin login successful', [
                'admin_login' => true
            ]);
        }

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Email could not be found.');
                }

                if ($user->isBanned()) {
                    throw new CustomUserMessageAuthenticationException('Your account has been banned. Contact support.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken)
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof User) {
            // Store user ID in session for custom authentication
            $request->getSession()->set('user_id', $user->getId());

            // Check if user has Organizer role
            if (in_array('Organizer', $user->getRoles())) {
                return new RedirectResponse($this->urlGenerator->generate('organizer_home'));
            }

            // Check if user is admin
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return new RedirectResponse($this->urlGenerator->generate('admin_home'));
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('user_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Check if this is the admin login special case
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $data = $exception->getMessageData();
            if (isset($data['admin_login']) && $data['admin_login'] === true) {
                // Admin login successful, redirect to admin home
                return new RedirectResponse($this->urlGenerator->generate('admin_home'));
            }
        }

        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_error', $exception);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
