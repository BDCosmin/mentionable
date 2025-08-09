<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $error = ' ';

        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var string $plainPassword */
                $plainPassword = $form->get('password')->getData();

                /** @var UploadedFile|null $avatarFile */
                $avatarFile = $form->get('avatar')->getData();
                $selectedDefaultAvatar = $form->get('selectedAvatar')->getData();

                if ($avatarFile && $selectedDefaultAvatar) {
                    $this->addFlash('error', 'Please select either a default avatar or upload a custom one — not both.');
                    return $this->redirectToRoute('app_register');
                }

                if ($avatarFile) {
                    $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                    $avatarFile->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );

                    $user->setAvatar($newFilename);

                } elseif ($selectedDefaultAvatar) {
                    $sourcePath = $this->getParameter('assets_avatars_directory') . '/' . $selectedDefaultAvatar;

                    $newFilename = pathinfo($selectedDefaultAvatar, PATHINFO_FILENAME)
                        . '-' . uniqid() . '.' . pathinfo($selectedDefaultAvatar, PATHINFO_EXTENSION);

                    $destinationPath = $this->getParameter('avatars_directory') . '/' . $newFilename;

                    if (!file_exists($sourcePath)) {
                        $this->addFlash('error', 'Default avatar file not found.');
                        return $this->redirectToRoute('app_register');
                    }

                    if (!copy($sourcePath, $destinationPath)) {
                        $this->addFlash('error', 'Could not copy default avatar.');
                        return $this->redirectToRoute('app_register');
                    }

                    $user->setAvatar($newFilename);
                } else {
                    $this->addFlash('error', 'Please select a valid avatar or upload a custom file.');
                    return $this->redirectToRoute('app_register');
                }

                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
                $user->setCreationDate(new \DateTime());
                $user->setRoles(['ROLE_USER']);
                $user->setIsVerified(false);

                $entityManager->persist($user);
                $entityManager->flush();

                try {
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                        (new TemplatedEmail())
                            ->from(new Address('noreply@test-z0vklo67jrpl7qrx.mlsender.net', 'Mentionable App'))
                            ->to((string) $user->getEmail())
                            ->subject('Please Confirm your Email')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );
                } catch (\Exception $emailException) {
                    $this->addFlash('error', 'Error while sending the confirmation email: ' . $emailException->getMessage());
                }

                $security->login($user, 'form_login', 'main');

                return $this->redirectToRoute('app_user_email_verification_sent');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error has occurred while trying to log you in. Please try again later.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify', name: 'app_user_email_verification_sent')]
    public function userEmailVerificationSent(): Response
    {
        return $this->render('registration/email_verification_sent.html.twig');
    }

    #[Route('/verified', name: 'app_user_verified')]
    public function userVerified(): Response
    {
        return $this->render('registration/successful_verification.html.twig');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_user_verified');
    }
}
