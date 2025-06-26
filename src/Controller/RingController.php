<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Entity\Ring;
use App\Entity\User;
use App\Form\RingForm;
use App\Repository\RingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class RingController extends AbstractController
{
    #[Route('/rings/discover', name: 'app_rings_discover', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, RingRepository $ringRepository): Response
    {
        $error = '';

        $rings = $ringRepository->findBy([], ['createdAt' => 'DESC'], 4);

        $form = $this->createForm(RingForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Ring $ring */
            $ring = $form->getData();
            $ring->setUser($this->getUser());
            $ring->setCreatedAt(new \DateTime());

            $interestTitle = $form->get('interest')->getData();

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

            $banner = $form->get('banner')->getData();
            if ($banner) {
                $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();

                $banner->move($this->getParameter('rings_directory'), $newFilename);
                $ring->setBanner($newFilename);
            }

            $entityManager->persist($ring);
            $entityManager->flush();

            return $this->redirectToRoute('app_rings_discover');
        }

        return $this->render('ring/index.html.twig', [
            'divVisibility' => 'none',
            'error' => $error,
            'ringForm' => $form->createView(),
            'rings' => $rings
        ]);
    }

//    #[Route('/rings/new', name: 'app_new_ring', methods: ['GET', 'POST'])]
//    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
//    {
//        $error = '';
//
//        $form = $this->createForm(RingForm::class);
//        $form->handleRequest($request);
//
//        if ($request->isMethod('POST')) {
//            $title = $request->request->get('title');
//            $description = $request->request->get('description');
//            $interest = $request->request->get('interest');
//
//            if (empty(trim($title)) || empty(trim($description)) || empty(trim($interest))) {
//                $error = 'Error: Please check your inputs';
//                return $this->render('ring/index.html.twig', [
//                    'error' => $error,
//                    'divVisibility' => 'block',
//                ]);
//            } else {
//                $ring = new Ring();
//                $ring->setUser($this->getUser());
//                $ring->setTitle($title);
//                $ring->setDescription($description);
//                $ring->setCreatedAt(new \DateTime());
//
//                $existingInterest = $entityManager->getRepository(Interest::class)->findOneBy([
//                    'title' => $interest
//                ]);
//
//                if ($existingInterest) {
//                    $ring->setInterest($existingInterest);
//                } else {
//                    $ring->setInterest($interest);
//                }
//
//                $banner = $request->files->get('banner');
//                if ($banner) {
//                    $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
//                    $safeFilename = $slugger->slug($originalFilename);
//                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();
//
//                    $banner->move(
//                        $this->getParameter('rings_directory'),
//                        $newFilename
//                    );
//
//
//                    $ring->setBanner($banner);
//
//                }
//
//                $entityManager->persist($ring);
//                $entityManager->flush();
//
//                return $this->redirectToRoute('app_rings_discover');
//            }
//        }
//
//        return $this->render('ring/index.html.twig', [
//            'divVisibility' => 'none',
//            'error' => $error,
//            'ringForm' => $form->createView(),
//        ]);
//    }
}
