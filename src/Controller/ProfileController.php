<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\FriendRequest;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use App\Repository\FriendRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(FriendRequestRepository $friendRequestRepository): Response
    {
        $user = $this->getUser();
        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'friendRequests' => $friendRequests,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $newFilename = uniqid().'.'.$avatarFile->guessExtension();
                $avatarFile->move(
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );
                $user->setAvatar($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Avatarul a fost actualizat cu succes!');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password')]
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

            $this->addFlash('success', 'Parola a fost schimbată cu succes!');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/friends/add', name: 'app_profile_add_friend_by_nametag', methods: ['POST'])]
    public function addFriendByNametag(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $nametag = $request->request->get('friend_nametag');
        $friend = $userRepository->findOneBy(['nametag' => $nametag]);

        if (!$friend) {
            $this->addFlash('error', 'Utilizatorul cu acest nametag nu există.');
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();
        if ($user === $friend) {
            $this->addFlash('error', 'Nu te poți adăuga pe tine ca prieten!');
            return $this->redirectToRoute('app_profile');
        }

        // Verificăm dacă cererea de prietenie există deja
        $existingRequest = $entityManager->getRepository(FriendRequest::class)->findOneBy([
            'sender' => $user,
            'receiver' => $friend,
        ]);

        if ($existingRequest) {
            $this->addFlash('error', 'O cerere de prietenie a fost deja trimisă acestui utilizator.');
            return $this->redirectToRoute('app_profile');
        }

        // Creăm o nouă cerere de prietenie
        $friendRequest = new FriendRequest();
        $friendRequest->setSender($user);
        $friendRequest->setReceiver($friend);

        $entityManager->persist($friendRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Cererea de prietenie a fost trimisă!');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/accept/{id}', name: 'app_profile_accept_friend')]
    public function acceptFriend(FriendRequest $friendRequest, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $sender = $friendRequest->getSender();

        if ($friendRequest->getReceiver() !== $user) {
            $this->addFlash('error', 'Nu ai permisiunea de a accepta această cerere.');
            return $this->redirectToRoute('app_profile');
        }

        // Adăugăm relația de prietenie ÎN AMBELE SENSURI
        $user->addFriend($sender);
        $sender->addFriend($user);

        // Ștergem cererea de prietenie
        $entityManager->remove($friendRequest);

        $entityManager->persist($user);
        $entityManager->persist($sender);
        $entityManager->flush();

        $this->addFlash('success', 'Cererea de prietenie a fost acceptată!');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/reject/{id}', name: 'app_profile_reject_friend')]
    public function rejectFriend(FriendRequest $friendRequest, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($friendRequest->getReceiver() !== $user) {
            $this->addFlash('error', 'Nu ai permisiunea de a respinge această cerere.');
            return $this->redirectToRoute('app_profile');
        }

        // Ștergem cererea de prietenie
        $entityManager->remove($friendRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Cererea de prietenie a fost respinsă.');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/remove/{id}', name: 'app_profile_remove_friend')]
    public function removeFriend(User $friend, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $user->removeFriend($friend);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Prieten eliminat cu succes!');

        return $this->redirectToRoute('app_profile');
    }
}