<?php

namespace App\Controller;

use App\Repository\InterestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InterestController extends AbstractController
{
    #[Route('/interest/{id}', name: 'app_interest_show', methods: ['GET'])]
    public function showInterest(
        InterestRepository $interestRepository,
        int $id
    ): Response {
        $interest = $interestRepository->find($id);

        $error = '';
        $divVisibility = 'none';

        if (!$interest) {
            throw $this->createNotFoundException('Interest not found.');
        }

        $usersWithSameInterest = $interestRepository->findUsersWithInterest($interest->getTitle());

        return $this->render('interest/index.html.twig', [
            'interest' => $interest,
            'users' => $usersWithSameInterest,
            'divVisibility' => $divVisibility,
            'error' => $error,
        ]);
    }

}
