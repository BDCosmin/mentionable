<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Entity\Ring;
use App\Entity\RingMembers;
use App\Entity\User;
use App\Form\RingForm;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class RingController extends AbstractController
{
    #[Route('/rings/discover', name: 'app_rings_discover', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, RingMemberRepository $ringMemberRepository, SluggerInterface $slugger, RingRepository $ringRepository): Response
    {
        $latestrings = $ringRepository->findBy([], ['createdAt' => 'DESC'], 4);
        $mostPopularRings = $ringRepository->findTopRingsByPopularity(4);

        $members = $ringMemberRepository->findBy([]);
        $memberCounts = [];

        foreach ($members as $row) {
            $ringId = $row->getRing()->getId();
            if (!isset($memberCounts[$ringId])) {
                $memberCounts[$ringId] = 0;
            }
            $memberCounts[$ringId]++;
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
                    $ring->setCreatedAt(new \DateTime());

                    $existingInterest = $entityManager->getRepository(Interest::class)->findOneBy(['title' => $interestTitle]);

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
            'latestrings' => $latestrings,
            'mostPopularRings' => $mostPopularRings,
            'members' => $members,
            'memberCounts' => $memberCounts,
        ]);
    }

    #[Route('/ring/{id}', name: 'app_ring_show', methods: ['GET', 'POST'])]
    public function show(int $id,
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

        $noteVotes = $noteVoteRepository->findBy([]);

        foreach ($ringNotes as $note) {
            $note->mentionedUserId = $note->getMentionedUserId($em);
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

        return $this->render('ring/page.html.twig', [
            'divVisibility' => 'none',
            'ring' => $ring,
            'members' => $members,
            'ringNotes' => $ringNotes,
            'votesMap' => $votesMap,
            'error' => $error
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
    public function myRings(int $id, RingRepository $ringRepository, RingMemberRepository $ringMemberRepository): Response
    {
        $error = ' ';

        $user = $this->getUser();

        $rings = $ringRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $members = $ringMemberRepository->findBy([]);

        return $this->render('user/rings.html.twig', [
            'rings' => $rings,
            'ringsCount' => count($rings),
            'error' => $error,
            'divVisibility' => 'none',
            'members' => $members
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

        $member = $ringMemberRepository->findBy(['user' => $user]);

        $ring = $ringRepository->find($id);
        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }
        $ringNotes = $noteRepository->findBy(
            ['ring' => $ring, 'isFromRing' => 1],
            ['publicationDate' => 'DESC']
        );

        $noteVotes = $noteVoteRepository->findBy([]);

        foreach ($ringNotes as $note) {
            $note->mentionedUserId = $note->getMentionedUserId($em);
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

        return $this->redirectToRoute('app_ring_show', ['id' => $id]);
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


}
