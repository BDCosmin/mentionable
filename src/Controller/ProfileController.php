<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\FriendRequest;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\FriendRequestRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/{id}', name: 'app_profile')]
    public function index(int $id, FriendRequestRepository $friendRequestRepository, UserRepository $userRepository, NotificationRepository $notificationRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);
        $notifications = $notificationRepository->findBy(['receiver' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'friendRequests' => $friendRequests,
            'notifications' => $notifications,
        ]);
    }
}