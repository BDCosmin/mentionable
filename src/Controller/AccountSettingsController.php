<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\CommentVoteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AccountSettingsController extends AbstractController
{
    #[Route('/avatar/edit', name: 'app_avatar_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                $avatarFile->move(
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );

                $user->setAvatar($newFilename);
            }


            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'The avatar has been updated successfully!');

            return $this->redirectToRoute('app_avatar_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newPassword = $data['newPassword'];
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password has been changed successfully!');

            return $this->redirectToRoute('app_change_password');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(UserRepository $userRepository, TokenStorageInterface $tokenStorage, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_user', $request->request->get('_token'))) {

            // Logout user: șterge tokenul de autentificare
            $tokenStorage->setToken(null);

            // Șterge user-ul din DB
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Your account has been deleted.');

            // Redirect către login (sau altă pagină publică)
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Invalid token.');
        return $this->redirectToRoute('homepage');
    }
}