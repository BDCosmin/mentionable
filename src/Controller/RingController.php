<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Ring;
use App\Entity\RingMembers;
use App\Entity\User;
use App\Form\RingForm;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class RingController extends AbstractController
{
    #[Route('/rings/discover', name: 'app_rings_discover', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, RingMemberRepository $ringMemberRepository, SluggerInterface $slugger, RingRepository $ringRepository): Response
    {
        $user = $this->getUser();

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


        $form = $this->createForm(RingForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var Ring $ring */
                $ring = $form->getData();

                $interestTitle = $form->get('interest')->getData();
                $banner = $form->get('banner')->getData();

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
                        $newInterest->setUser($this->getUser());
                        $entityManager->persist($newInterest);
                        $ring->setInterest($newInterest);
                    }

                    $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();

                    $banner->move($this->getParameter('rings_directory'), $newFilename);
                    $ring->setBanner($newFilename);

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
            'divVisibility' => 'none',
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
        SluggerInterface $slugger
    ): Response {
        $ring = $ringRepository->find($id);

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
            $normalizedTitle = strtolower($interestTitle);

            $existingInterest = $em->getRepository(Interest::class)
                ->createQueryBuilder('i')
                ->where('LOWER(TRIM(i.title)) = :title')
                ->setParameter('title', $normalizedTitle)
                ->getQuery()
                ->getOneOrNullResult();

            $oldInterest = $ring->getInterest();

            if ($existingInterest) {
                $ring->setInterest($existingInterest);
            } else {
                $newInterest = new Interest();
                $newInterest->setTitle($interestTitle);
                $newInterest->setUser($this->getUser());
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
            'ring' => $ring
        ]);
    }

    #[Route('/ring/{id}', name: 'app_ring_show', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        RingRepository $ringRepository,
        NoteRepository $noteRepository,
        RingMemberRepository $ringMemberRepository,
        NoteVoteRepository $noteVoteRepository,
        EntityManagerInterface $em
    ): Response
    {
        $error = '';
        $user = $this->getUser();

        $ring = $ringRepository->find($id);
        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }

        $ringNotes = $noteRepository->findBy(
            ['ring' => $ring, 'isFromRing' => 1],
            ['publicationDate' => 'DESC']
        );
        $members = $ringMemberRepository->findBy(['ring' => $ring]);
        $owner = $ringMemberRepository->findOneBy(['ring' => $ring, 'role' => 'owner']);

        $rolesMap = [];
        foreach ($members as $member) {
            $rolesMap[$member->getUser()->getId()] = $member->getRole(); // ex: 'owner', 'moderator', etc.
        }

        $noteVotes = $noteVoteRepository->findBy([]);

        // Build array for notes + mentionedUser
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

        return $this->render('ring/page.html.twig', [
            'divVisibility' => 'none',
            'ring' => $ring,
            'members' => $members,
            'ringNotes' => $notesWithMentionedUser,
            'votesMap' => $votesMap,
            'error' => $error,
            'owner' => $owner,
            'rolesMap' => $rolesMap,
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

        // Toate ringurile create de user (owner)
        $ownerRings = $ringRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        // Adună toți membrii pentru ringurile astea
        $ringIds = array_map(fn($ring) => $ring->getId(), $ownerRings);

        // Membrii tuturor ringurilor pe care userul le deține
        $members = $ringMemberRepository->findBy(['ring' => $ringIds]);

        // Creează un map ringId => membri (array)
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
        RingRepository $ringRepository
    ): Response
    {
        $user = $userRepository->find($id);
        $ring = $ringRepository->find($ringId);

        if (!$user || !$ring) {
            $this->addFlash('error', 'User or Ring not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $ringMember = $ringMemberRepository->findOneBy(['user' => $user, 'ring' => $ring]);

        if (!$ringMember) {
            $this->addFlash('error', 'Member not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $em->remove($ringMember);
        $em->flush();

        $this->addFlash('success', 'Member deleted successfully.');

        return $this->redirect($request->headers->get('referer') ?? $this->redirectToRoute('app_ring_show', ['ringId' => $ringId]));
    }

    #[Route('/ring/{ringId}-{id}/change-member-role', name: 'app_ring_change_member_role', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function changeRoleMember(int $id, int $ringId, Request $request, EntityManagerInterface $em, RingMemberRepository $ringMemberRepository): Response
    {
        $user = $this->getUser();
        $ringMember = $ringMemberRepository->findOneBy(['user' => $id, 'ring' => $ringId]);
        $currentRingMember = $ringMemberRepository->findOneBy(['user' => $user, 'ring' => $ringId]);

        if (!$ringMember) {
            $this->addFlash('error', 'Member not found.');
            return $this->redirectToRoute('app_rings_discover');
        }

        $ringMember->setRole('owner');
        $currentRingMember->setRole('member');
        $em->persist($ringMember);
        $em->persist($currentRingMember);

        $em->flush();

        $this->addFlash('success', 'Roles upgraded successfully.');

        return $this->redirect($request->headers->get('referer') ?? $this->redirectToRoute('app_ring_show', ['ringId' => $ringId]));
    }

}
