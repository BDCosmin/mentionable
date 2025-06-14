<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/login/forgot-password', name: 'app_login_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        CacheInterface $cache
    ): Response {
        $form = $this->createForm(ForgotPasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $newPassword = $form->get('newPassword')->getData();

            $existingUser = $userRepository->findOneBy(['email' => $email]);

            if (!$existingUser) {
                $this->addFlash('error', 'No account found with this email.');
            } else {
                $token = bin2hex(random_bytes(32));

                $hashedPassword = $passwordHasher->hashPassword($existingUser, $newPassword);
                $cache->get('reset_' . $token, function (ItemInterface $item) use ($email, $hashedPassword) {
                    $item->expiresAfter(1800); // 30 min
                    return ['email' => $email, 'hashedPassword' => $hashedPassword];
                });

                $link = $this->generateUrl('app_verify_password_reset', [
                    'token' => $token
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new Email())
                    ->from('no-reply@main-bvxea6i-6bedjzifhnlai.uk-1.platformsh.site')
                    ->to($email)
                    ->subject('Mentionable - Confirm Your Password Change')
                    ->html("<p>Click below to confirm your password change:</p><a href='$link'>Confirm Password Change</a>");

                $mailer->send($emailMessage);

                $this->addFlash('success', 'A verification link was sent to your email.');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login/verify-password-reset/{token}', name: 'app_verify_password_reset')]
    public function verifyPasswordReset(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        CacheInterface $cache
    ): Response {
        $data = $cache->getItem('reset_' . $token);

        if (!$data->isHit()) {
            $this->addFlash('error', 'Invalid or expired token.');
            return $this->redirectToRoute('app_login');
        }

        $info = $data->get();
        $user = $userRepository->findOneBy(['email' => $info['email']]);

        if (!$user) {
            $this->addFlash('error', 'User no longer exists.');
            return $this->redirectToRoute('app_login');
        }

        $user->setPassword($info['hashedPassword']);
        $entityManager->flush();

        $cache->deleteItem('reset_' . $token);

        $this->addFlash('success', 'Password successfully updated. You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
