<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Entity\Ring;
use App\Entity\RingMembers;
use App\Entity\User;
use App\Form\RingForm;
use App\Repository\CommentVoteRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\TextModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class RingController extends AbstractController
{
    #[Route('/rings/discover', name: 'app_rings_discover', methods: ['GET', 'POST'])]
    public function index(Request $request,
                          EntityManagerInterface $entityManager,
                          RingMemberRepository $ringMemberRepository,
                          SluggerInterface $slugger,
                          RingRepository $ringRepository,
                          TextModerationService $moderator
    ): Response
    {
        $user = $this->getUser();

        $divVisibility = 'none';

        $latestRings = $ringRepository->findBy([], ['createdAt' => 'DESC'], 4);
        $mostPopularRings = $ringRepository->findTopRingsByPopularity(4);

        $memberOf = $ringMemberRepository->findLastJoinedRingsByUser($user);
        $joinedRings = array_map(fn($membership) => $membership->getRing(), $memberOf);

        $members = $ringMemberRepository->findBy([]);
        $memberCounts = [];

        foreach ($members as $row) {
            $ringId = $row->getRing()->getId();
            if (!isset($memberCounts[$ringId])) {
                $memberCounts[$ringId] = 0;
            }
            $memberCounts[$ringId]++;
        }

        $owners = $ringMemberRepository->findBy(['role' => 'owner']);
        $ownersMap = [];
        foreach ($owners as $ownerMember) {
            $ringId = $ownerMember->getRing()->getId();
            $ownersMap[$ringId] = $ownerMember->getUser();
        }

        // ADD RING
        $form = $this->createForm(RingForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var Ring $ring */
                $ring = $form->getData();

                $interestTitle = $form->get('interest')->getData();
                $banner = $form->get('banner')->getData();
                $description = $form->get('description')->getData();
                $ringTitle = $form->get('title')->getData();

                $ringInfo = [$ringTitle, $description, $interestTitle];
                foreach ($ringInfo as $info) {
                    $moderation = $moderator->analyze($info);
                    $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
                    $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

                    if ($toxicityScore > 0.70 || $insultScore > 0.70) {
                        $error = 'Your ring creation was cancelled because some of information may be toxic or insulting.';
                        $divVisibility = 'block';

                        return $this->render('ring/index.html.twig', [
                            'divVisibility' => $divVisibility,
                            'ringForm' => $form->createView(),
                            'latestrings' => $latestRings,
                            'mostPopularRings' => $mostPopularRings,
                            'members' => $members,
                            'memberCounts' => $memberCounts,
                            'joinedRings' => $joinedRings,
                            'owners' => $owners,
                            'ownersMap' => $ownersMap,
                            'error' => $error,
                        ]);
                    }
                }

                if (!$banner) {
                    $this->addFlash('error', 'Please upload a banner image.');
                } else {
                    $ring->setUser($this->getUser());
                    $newMember = new RingMembers();
                    $newMember->setRing($ring);
                    $newMember->setUser($user);
                    $newMember->setRole('owner');
                    $newMember->setStatus('OK');
                    $newMember->setJoinedAt(new \DateTime());
                    $ring->setCreatedAt(new \DateTime());

                    $existingInterest = $entityManager->getRepository(Interest::class)
                        ->createQueryBuilder('i')
                        ->where('LOWER(TRIM(i.title)) = :title')
                        ->setParameter('title', strtolower(trim($interestTitle)))
                        ->getQuery()
                        ->getOneOrNullResult();


                    if ($existingInterest) {
                        $ring->setInterest($existingInterest);
                    } else {
                        $newInterest = new Interest();
                        $newInterest->setTitle($interestTitle);
                        $entityManager->persist($newInterest);
                        $ring->setInterest($newInterest);
                    }

                    $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();

                    $banner->move($this->getParameter('rings_directory'), $newFilename);
                    $ring->setBanner($newFilename);
                    $ring->setIsSuspended(false);

                    $entityManager->persist($ring);
                    $entityManager->persist($newMember);
                    $entityManager->flush();

                    $this->addFlash('success', 'Community ring created successfully!');
                    return $this->redirectToRoute('app_rings_discover');
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }

                $interestTitle = $form->get('interest')->getData();
                if (empty($interestTitle)) {
                    $this->addFlash('error', 'Interest cannot be blank.');
                }
            }
        }

        return $this->render('ring/index.html.twig', [
            'divVisibility' => $divVisibility,
            'ringForm' => $form->createView(),
            'latestrings' => $latestRings,
            'mostPopularRings' => $mostPopularRings,
            'members' => $members,
            'memberCounts' => $memberCounts,
            'joinedRings' => $joinedRings,
            'owners' => $owners,
            'ownersMap' => $ownersMap,
        ]);
    }

    /**
     * @throws ORMException
     */
    #[Route('/ring/{id}/update', name: 'app_ring_update', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        int $id,
        Request $request,
        RingRepository $ringRepository,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        TextModerationService $moderator
    ): Response {
        $ring = $ringRepository->find($id);

        $divVisibility = 'none';

        if (!$ring) {
            $this->addFlash('error', 'Ring not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        if ($ring->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You are not authorized to edit this ring.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $form = $this->createForm(RingForm::class, $ring);
        $form->get('interest')->setData($ring->getInterest()?->getTitle());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $banner */
            $banner = $form->get('banner')->getData();

            if ($banner) {
                $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();

                $banner->move($this->getParameter('rings_directory'), $newFilename);
                $ring->setBanner($newFilename);
            }

            $interestTitle = trim($form->get('interest')->getData());
            $normalizedInterestTitle = strtolower($interestTitle);
            $description = $form->get('description')->getData();
            $ringTitle = $form->get('title')->getData();

            $ringInfo = [$ringTitle, $description, $normalizedInterestTitle];
            foreach ($ringInfo as $info) {
                $moderation = $moderator->analyze($info);
                $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
                $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

                if ($toxicityScore > 0.70 || $insultScore > 0.70) {
                    $error = 'Your ring update was cancelled because some of information may be toxic or insulting.';
                    $divVisibility = 'block';

                    return $this->render('ring/update.html.twig', [
                        'ringForm' => $form->createView(),
                        'ring' => $ring,
                        'error' => $error,
                        'divVisibility' => $divVisibility,
                    ]);
                }
            }

            $existingInterest = $em->getRepository(Interest::class)
                ->createQueryBuilder('i')
                ->where('LOWER(TRIM(i.title)) = :title')
                ->setParameter('title', $normalizedInterestTitle)
                ->getQuery()
                ->getOneOrNullResult();

            $oldInterest = $ring->getInterest();

            if ($existingInterest) {
                $ring->setInterest($existingInterest);
            } else {
                $newInterest = new Interest();
                $newInterest->setTitle($interestTitle);
                $em->persist($newInterest);

                $ring->setInterest($newInterest);
            }

            if ($oldInterest && $oldInterest !== $ring->getInterest()) {
                $otherRings = $em->getRepository(Ring::class)->findBy(['interest' => $oldInterest]);

                if (count($otherRings) === 1) {
                    $em->remove($oldInterest);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Ring updated successfully!');
            return $this->redirectToRoute('app_rings_discover');
        }

        return $this->render('ring/update.html.twig', [
            'ringForm' => $form->createView(),
            'ring' => $ring,
            'divVisibility' => $divVisibility,
        ]);
    }

    #[Route('/ring/{id}/view/', name: 'app_ring_show', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        RingRepository $ringRepository,
        NoteRepository $noteRepository,
        RingMemberRepository $ringMemberRepository,
        NoteVoteRepository $noteVoteRepository,
        EntityManagerInterface $em,
        CommentVoteRepository $commentVoteRepository,
        Request $request
    ): Response
    {
        $error = '';
        $user = $this->getUser();

        $ring = $ringRepository->find($id);
        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }

        $memberships = $ringMemberRepository->findActiveMembershipsForUser($user);
        $ringIds = array_map(fn($m) => $m->getRing()->getId(), $memberships);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $ringNotes = $noteRepository->findNotesForRingWithPinnedFirst($ring->getId(), $limit, $offset);

        $members = $ringMemberRepository->findBy(['ring' => $ring]);
        $owner = $ringMemberRepository->findOneBy(['ring' => $ring, 'role' => 'owner']);

        $rolesMap = [];
        foreach ($members as $member) {
            $rolesMap[$member->getUser()->getId()] = $member->getRole();
        }

        $commentVotes = $commentVoteRepository->findBy(['user' => $user]);
        $commentVotesMap = [];
        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

        $noteVotes = $noteVoteRepository->findBy([]);

        $notesWithMentionedUser = [];
        foreach ($ringNotes as $note) {
            $mentionedUser = $note->getMentionedUser();
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser,
            ];
        }

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

        if ($ring->getIsSuspended() == 1) {
            $this->addFlash('danger', '<b>This ring has been suspended.</b> <br/> Reason: '.$ring->getSuspensionReason());
        }

        $limitedComments = [];
        foreach ($ringNotes as $note) {
            $comments = $note->getComments()->toArray();
            usort($comments, fn($a, $b) => $b->getPublicationDate() <=> $a->getPublicationDate());
            $limitedComments[$note->getId()] = array_slice($comments, 0, 5);
        }

        $favoritesMap = [];
        foreach ($ringNotes as $note) {
            $favoritesMap[$note->getId()] = $user->hasFavorite($note);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('ring/_ring_note.html.twig', [
                'ringNotes' => $notesWithMentionedUser,
                'commentVotesMap' => $commentVotesMap,
                'votesMap' => $votesMap,
                'rolesMap' => $rolesMap,
                'limitedComments' => $limitedComments,
                'favoritesMap' => $favoritesMap,
                'isMember' => in_array($ring->getId(), $ringIds),
                'ring' => $ring,
            ]);
        }

        return $this->render('ring/page.html.twig', [
            'divVisibility' => 'none',
            'ring' => $ring,
            'commentVotesMap' => $commentVotesMap,
            'members' => $members,
            'ringNotes' => $notesWithMentionedUser,
            'votesMap' => $votesMap,
            'error' => $error,
            'owner' => $owner,
            'rolesMap' => $rolesMap,
            'limitedComments' => $limitedComments,
            'favoritesMap' => $favoritesMap,
            'page' => $page,
        ]);
    }

    #[Route('/ring/{id}/delete', name: 'app_ring_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, Request $request, EntityManagerInterface $em, RingRepository $ringRepository): Response
    {
        $ring = $ringRepository->find($id);

        if (!$ring) {
            $this->addFlash('error', 'Ring not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $em->remove($ring);
        $em->flush();

        $interest = $ring->getInterest();
        $ringCount = $ringRepository->count(['interest' => $interest]);

        if ($interest && $ringCount === 0 && $interest->getUser() === null) {
            $em->remove($interest);
            $em->flush();
        }

        $this->addFlash('success', 'Ring deleted successfully.');

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_rings_discover'));
    }

    #[Route('/rings/{id}/dashboard', name: 'app_my_rings', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myRings(
        RingRepository $ringRepository,
        RingMemberRepository $ringMemberRepository
    ): Response {
        $user = $this->getUser();

        $ownerRings = $ringRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $ringIds = array_map(fn($ring) => $ring->getId(), $ownerRings);

        $members = $ringMemberRepository->findBy(['ring' => $ringIds]);

        $membersByRing = [];
        foreach ($members as $member) {
            $ringId = $member->getRing()->getId();
            $membersByRing[$ringId][] = $member;
        }

        $membersCountByRing = [];
        foreach ($members as $member) {
            $ringId = $member->getRing()->getId();
            if (!isset($membersCountByRing[$ringId])) {
                $membersCountByRing[$ringId] = 0;
            }
            $membersCountByRing[$ringId]++;
        }

        return $this->render('user/rings.html.twig', [
            'rings' => $ownerRings,
            'ringsCount' => count($ownerRings),
            'membersByRing' => $membersByRing,
            'membersCountByRing' => $membersCountByRing,
            'user' => $user,
        ]);
    }

    #[Route('/ring/{id}/join', name: 'app_join_ring', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(int $id,
                         RingRepository $ringRepository,
                         NoteRepository $noteRepository,
                         RingMemberRepository $ringMemberRepository,
                         NoteVoteRepository $noteVoteRepository,
                         EntityManagerInterface $em,
    ): Response
    {
        $error = '';
        $user = $this->getUser();
        $ring = $ringRepository->find($id);

        $member = $ringMemberRepository->findOneBy([
            'user' => $user,
            'ring' => $ring,
        ]);

        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }
        $ringNotes = $noteRepository->findBy(
            ['ring' => $ring, 'isFromRing' => 1],
            ['publicationDate' => 'DESC']
        );

        $noteVotes = $noteVoteRepository->findBy([]);

        $notesWithMentionedUser = [];
        foreach ($ringNotes as $note) {
            $mentionedUser = $note->getMentionedUser();
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser,
            ];
        }

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }

        if(!$member)
        {
            $newMember = new RingMembers();
            $newMember->setRing($ring);
            $newMember->setUser($user);
            $newMember->setRole('member');
            $newMember->setStatus('OK');
            $newMember->setJoinedAt(new \DateTime());

            $em->persist($newMember);
            $em->flush();
        }

        return $this->redirectToRoute('app_ring_show', ['id' => $id, 'ringNotes' => $notesWithMentionedUser]);
    }

    #[Route('/ring/{id}/leave', name: 'app_leave_ring', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(
        int $id,
        RingRepository $ringRepository,
        RingMemberRepository $ringMemberRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        $ring = $ringRepository->find($id);
        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }

        $existingMember = $ringMemberRepository->findOneBy([
            'user' => $user,
            'ring' => $ring,
        ]);

        if ($existingMember) {
            $em->remove($existingMember);
            $em->flush();
        }

        return $this->redirectToRoute('app_ring_show', ['id' => $id]);
    }

    #[Route('/ring/{ringId}-{id}/remove-member', name: 'app_ring_remove_member', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function removeMember(
        int $id,
        int $ringId,
        Request $request,
        EntityManagerInterface $em,
        RingMemberRepository $ringMemberRepository,
        UserRepository $userRepository,
        RingRepository $ringRepository,
        NotificationService $notificationService
    ): Response
    {
        $user = $userRepository->find($id);
        $ring = $ringRepository->find($ringId);

        if (!$user || !$ring) {
            $this->addFlash('error', 'User or Ring not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $ringMember = $ringMemberRepository->findOneBy(['user' => $user, 'ring' => $ring]);

        $sender = $this->getUser();
        $receiver = $ringMember->getUser();

        if (!$ringMember) {
            $this->addFlash('error', 'Member not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $em->remove($ringMember);
        $em->flush();

        $this->addFlash('success', 'Member deleted successfully.');

        $notification = $notificationService->notifyRingMemberKick($sender, $receiver, $ring);

        if ($request->isXmlHttpRequest()) {
            $ringTitle = null;
            $ringBanner = null;
            if ($notification->getRing() !== null) {
                $ringTitle = $notification->getRing()->getTitle();
                $ringBanner = $notification->getRing()->getBanner();
            }

            return new JsonResponse([
                'success' => true,
                'notification' => [
                    'ring' => [
                        'title' => $ringTitle,
                        'banner' => $ringBanner
                    ],
                ]
            ]);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_ring_show', ['ringId' => $ringId]));
    }

    #[Route('/ring/{ringId}-{id}/change-member-role', name: 'app_ring_change_member_role', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function changeRoleMember(int $id, int $ringId, Request $request, RingRepository $ringRepository, NotificationService $notificationService, EntityManagerInterface $em, UserRepository $userRepository,  RingMemberRepository $ringMemberRepository): Response
    {
        $user = $this->getUser();
        $ring = $ringRepository->find($ringId);

        $ringMember = $ringMemberRepository->findOneBy(['user' => $id, 'ring' => $ringId]);
        $userRingMember = $ringMember->getUser();

        $currentRingMember = $ringMemberRepository->findOneBy(['user' => $user, 'ring' => $ringId]);

        if (!$userRingMember){
            $this->addFlash('error', 'Member not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $ringMember->setRole('owner');

        $ring->setUser($userRingMember);
        $currentRingMember->setRole('member');

        $sender = $this->getUser();
        $receiver = $ringMember->getUser();

        $em->persist($ringMember);
        $em->persist($ring);
        $em->persist($currentRingMember);

        $em->flush();

        $this->addFlash('success', 'Roles upgraded successfully.');

        $notification = $notificationService->notifyRingRoleUpgrade($sender, $receiver, $ring);

        if ($request->isXmlHttpRequest()) {
            $ringTitle = null;
            $ringBanner = null;
            if ($notification->getRing() !== null) {
                $ringTitle = $notification->getRing()->getTitle();
                $ringBanner = $notification->getRing()->getBanner();
            }

            return new JsonResponse([
                'success' => true,
                'notification' => [
                    'ring' => [
                        'title' => $ringTitle,
                        'banner' => $ringBanner
                    ],
                ]
            ]);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_ring_show', ['ringId' => $ringId]));
    }

}
