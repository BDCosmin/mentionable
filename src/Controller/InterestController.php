<?php

namespace App\Controller;

use App\Repository\InterestRepository;
use App\Repository\RingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InterestController extends AbstractController
{
    #[Route('/interest/{id}', name: 'app_interest_show', methods: ['GET'])]
    public function showInterest(
        InterestRepository $interestRepository,
        RingRepository $ringRepository,
        int $id
    ): Response {
        $interest = $interestRepository->find($id);

        $error = '';
        $divVisibility = 'none';

        if (!$interest) {
            throw $this->createNotFoundException('Interest not found.');
        }

        $usersWithSameInterest = $interestRepository->findUsersWithInterest($interest->getTitle());

        // ✅ Rings care au același interest
        $ringsWithSameInterest = $ringRepository->findBy(['interest' => $interest]);

        return $this->render('interest/index.html.twig', [
            'interest' => $interest,
            'users' => $usersWithSameInterest,
            'rings' => $ringsWithSameInterest,
            'divVisibility' => $divVisibility,
            'error' => $error,
        ]);
    }

    #[Route('/interests', name: 'app_interests_list', methods: ['GET'])]
    public function showListInterests(
        InterestRepository $interestRepository
    ): Response {

        $interests = $interestRepository->findBy([]);

        $error = '';
        $divVisibility = 'none';

        if (!$interests) {
            throw $this->createNotFoundException('Interests not found.');
        }

        return $this->render('interest/list.html.twig', [
            'interests' => $interests,
            'divVisibility' => $divVisibility,
            'error' => $error,
        ]);
    }

}
