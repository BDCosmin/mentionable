<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Repository\InterestRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\NotificationRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use App\Repository\FriendRequestRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile/{id}/view', name: 'app_profile')]
    public function index(
        int $id,
        FriendRequestRepository $friendRequestRepository,
        RingRepository $ringRepository,
        NoteVoteRepository $noteVoteRepository,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
        RingMemberRepository $ringMemberRepository,
    ): Response
    {
        $user = $userRepository->find($id);
        $currentUser = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $noteVotes = $noteVoteRepository->findBy([]);
        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);
        $notifications = $notificationRepository->findBy(['receiver' => $user]);
        $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['publicationDate' => 'DESC']);
        $interests = $user->getInterests();
        $rings = $ringRepository->findByUserOrMember($user);
        $ringsMobileDisplay = $ringRepository->findByUserOrMemberLimit($user);

        // Build array for notes + mentionedUser
        $notesWithMentionedUser = [];
        foreach ($notes as $note) {
            $mentionedUser = $note->getMentionedUser(); // Fetch the User object
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser, // Pass the User object, not an ID
            ];
        }

        // Build votes map
        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $currentUser) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

        $rolesMap = [];
        foreach ($notes as $note) {
            $ring = $note->getRing();
            $author = $note->getUser();

            if ($ring && $author) {
                $member = $ringMemberRepository->findOneBy([
                    'ring' => $ring,
                    'user' => $author,
                ]);

                if ($member) {
                    $rolesMap[$author->getId() . '_' . $ring->getId()] = $member->getRole();
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'notesWithMentionedUser' => $notesWithMentionedUser,
            'friendRequests' => $friendRequests,
            'notifications' => $notifications,
            'interests' => $interests,
            'noteVotes' => $noteVotes,
            'votesMap' => $votesMap,
            'rings' => $rings,
            'ringsMobileDisplay' => $ringsMobileDisplay,
            'roles' => $rolesMap,
        ]);
    }

    #[Route('/profile/interest/new', name: 'app_interest_new', methods: ['GET', 'POST'])]
    public function interest(Request $request,
                        EntityManagerInterface $em,
                        NotificationService $notificationService,
                        SluggerInterface $slugger,
    ): Response
    {
        $error = '';

        $user = $this->getUser();
        if (count($user->getInterests()) >= 5) {
            $this->addFlash('error', 'You can only have up to 5 interests.');
            return $this->redirect($request->headers->get('referer'));
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('interest-content');

            if (empty(trim($content)) || preg_match('/\s/', $content)) {
                $this->addFlash('error', 'Interest cannot contain spaces.');
                return $this->redirect($request->headers->get('referer'));
            } else {
                $interest = new Interest();
                $interest->setUser($this->getUser());
                $interest->setTitle($content);

                $em->persist($interest);
                $em->flush();
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/profile/interest/delete/{id}', name: 'app_interest_delete', methods: ['DELETE'])]
    public function deleteInterest(Request $request,
                             EntityManagerInterface $em,
                             NotificationService $notificationService,
                             SluggerInterface $slugger,
                             InterestRepository $interestRepository,
                             int $id
    ): JsonResponse
    {
        $user = $this->getUser();
        $interest = $interestRepository->find($id);

        if (!$interest) {
            return new JsonResponse(['success' => false, 'message' => 'Interest not found.'], 404);
        }

        if ($interest->getUser() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $em->remove($interest);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}