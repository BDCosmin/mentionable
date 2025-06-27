<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Entity\Ring;
use App\Entity\User;
use App\Form\RingForm;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingRepository;
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
    public function index(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, RingRepository $ringRepository): Response
    {
        $rings = $ringRepository->findBy([], ['createdAt' => 'DESC'], 4);

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
                // Ia toate erorile de la form și le afișează
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }

                // Validări personalizate pentru câmpurile mapped false
                $interestTitle = $form->get('interest')->getData();
                if (empty($interestTitle)) {
                    $this->addFlash('error', 'Interest cannot be blank.');
                }
            }
        }

        return $this->render('ring/index.html.twig', [
            'divVisibility' => 'none',
            'ringForm' => $form->createView(),
            'rings' => $rings
        ]);
    }

    #[Route('/ring/{id}', name: 'app_ring_show', methods: ['GET', 'POST'])]
    public function show(int $id, RingRepository $ringRepository): Response
    {
        $user = $this->getUser();
        $ring = $ringRepository->find($id);

        if (!$ring) {
            return $this->redirectToRoute('app_rings_discover');
        }

        return $this->render('ring/page.html.twig', [
            'divVisibility' => 'none',
            'ring' => $ring,
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

    #[Route('/rings/my', name: 'app_my_rings', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myRings(RingRepository $ringRepository): Response
    {
        $error = ' ';


        $user = $this->getUser();

        $rings = $ringRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('user/rings.html.twig', [
            'rings' => $rings,
            'ringsCount' => count($rings),
            'error' => $error,
            'divVisibility' => 'none',
        ]);
    }


}
