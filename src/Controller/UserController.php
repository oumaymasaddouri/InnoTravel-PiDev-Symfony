<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;


class UserController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userCountry = $user->getCountry();
            $userPhoneNumber = $user->getPhoneNumber();

            // Find the country in the database
            $country = $em->getRepository(\App\Entity\Country::class)->findOneBy(['name' => $userCountry]);

            if (!$country) {
                $this->addFlash('danger', 'Invalid country selected.');
                return $this->redirectToRoute('app_register');
            }

            $expectedLength = $country->getPhoneNumberLength();
            $prefix = $country->getPhonePrefix();

            if (strlen($userPhoneNumber) !== $expectedLength) {
                $this->addFlash('danger', "Phone number must be exactly {$expectedLength} digits for {$userCountry}.");
                return $this->redirectToRoute('app_register');
            }

            // Optionally: Attach prefix automatically
            $user->setPhoneNumber($prefix . $userPhoneNumber);

            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', '✅ User registered successfully!');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        SessionInterface $session,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');

            $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@innotravel.tn';
            $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'change_this_password';

            if ($email === $adminEmail && $password === $adminPassword) {
                $session->set('admin', true);
                return $this->redirectToRoute('admin_home');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user && $passwordHasher->isPasswordValid($user, $password)) {
                if ($user->isBanned()) {
                    $this->addFlash('danger', '⛔ Your account is banned. Contact support.');
                    return $this->redirectToRoute('app_login');
                }

                $session->set('user_id', $user->getId());

                // Check if user has Organizer role
                if (in_array('Organizer', $user->getRoles())) {
                    return $this->redirectToRoute('organizer_home');
                }

                return $this->redirectToRoute('user_home');
            }

            $this->addFlash('danger', 'Invalid credentials.');
        }

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/user/home', name: 'user_home')]
    public function userHome(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/home.html.twig', ['user' => $user]);
    }

    #[Route('/admin/home', name: 'admin_home')]
    public function adminHome(SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('basebackoffice.html.twig');
    }

    #[Route('/user/account', name: 'user_account')]
    public function account(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $feedback = $em->getRepository(\App\Entity\Feedback::class)
            ->findOneBy(['userId' => $user]);

        return $this->render('user/userAccount.html.twig', [
            'user' => $user,
            'feedback' => $feedback
        ]);
    }

    #[Route('/account/edit', name: 'account_edit')]
    public function accountEdit(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user, ['include_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Account updated successfully.');
            return $this->redirectToRoute('user_account');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/account/delete', name: 'account_delete')]
    public function accountDeleteConfirm(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/delete_confirm.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/account/delete/confirm', name: 'account_delete_confirm')]
    public function accountDelete(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);

        if ($user) {
            $em->remove($user);
            $em->flush();
            $session->invalidate();
            $this->addFlash('danger', 'Your account has been deleted.');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('danger', 'User deleted!');
        return $this->redirectToRoute('admin');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->invalidate();
        return $this->redirectToRoute('app_login');
    }

    #[Route('/settings', name: 'app_settings')]
    public function settings(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('/user/settings.html.twig', ['user' => $user]);
    }

    #[Route('/user/hotels', name: 'user_hotels')]
    public function userHotels(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $hotels = $em->getRepository(\App\Entity\Hotel::class)->findAll();

        return $this->render('user/hotels.html.twig', [
            'user' => $user,
            'hotels' => $hotels
        ]);
    }

    private function getUserFromSession(SessionInterface $session, EntityManagerInterface $em): ?User
    {
        return $session->get('user_id') ? $em->getRepository(User::class)->find($session->get('user_id')) : null;
    }

    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', '❌ Email not found.');
                return $this->redirectToRoute('forgot_password');
            }

            $verificationCode = random_int(100000, 999999);
            $session->set('verification_code', $verificationCode);
            $session->set('reset_email', $email);

            // Use manual Transport and Mailer (working setup)
            $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
            $mailer = new Mailer($transport);

            $htmlContent = <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Reset Your InnoTravel Password</title>
                </head>
                <body style="font-family: Arial, sans-serif; background-color: #f3f4f6; padding: 40px; color: #333;">
                    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
                        <h2 style="color: #0073ff; text-align: center;">🔐 Password Reset Request</h2>
                        <p>Hi there,</p>
                        <p>We received a request to reset your password for your InnoTravel account. Use the code below to reset it:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <span style="font-size: 24px; letter-spacing: 4px; background-color: #fef08a; color: #000; padding: 10px 20px; border-radius: 8px; display: inline-block; font-weight: bold;">
                                {$verificationCode}
                            </span>
                        </div>
                        <p>If you didn't request this, you can safely ignore this email.</p>
                        <p style="margin-top: 30px;">Thanks,<br><strong>The InnoTravel Team</strong></p>
                        <hr style="margin: 40px 0;">
                        <p style="font-size: 12px; color: #999; text-align: center;">
                            This is an automated message. Please do not reply.
                        </p>
                    </div>
                </body>
                </html>
                HTML;

            $senderEmail = $_ENV['SENDER_EMAIL'] ?? 'noreply@innotravel.com';

            $emailMessage = (new Email())
                ->from(new Address($senderEmail, 'InnoTravel'))
                ->to($user->getEmail())
                ->subject('🔐 Password Reset Verification Code')
                ->html($htmlContent);

            try {
                $mailer->send($emailMessage);
                $this->addFlash('success', '✅ Verification code sent! Check your inbox.');
            } catch (\Throwable $e) {
                $this->addFlash('danger', '❌ Failed to send email: ' . $e->getMessage());
            }

            return $this->redirectToRoute('reset_password');
        }

        return $this->render('user/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'reset_password')]
    public function resetPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, SessionInterface $session): Response
    {
        $code = $session->get('verification_code');
        $email = $session->get('reset_email');

        if (!$code || !$email) {
            $this->addFlash('danger', '❌ Please request a reset code first.');
            return $this->redirectToRoute('forgot_password');
        }

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('code');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($enteredCode != $code) {
                $this->addFlash('danger', '❌ Incorrect verification code.');
                return $this->redirectToRoute('reset_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', '❌ Passwords do not match.');
                return $this->redirectToRoute('reset_password');
            }

            if (strlen($newPassword) < 8) {
                $this->addFlash('danger', '❌ Password must be at least 8 characters.');
                return $this->redirectToRoute('reset_password');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $em->flush();

                $session->remove('verification_code');
                $session->remove('reset_email');

                $this->addFlash('success', '✅ Password changed successfully!');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('user/reset_password.html.twig');
    }
}
