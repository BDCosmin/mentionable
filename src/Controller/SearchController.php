<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_find_nametag', methods: ['POST'])]
    public function search(Request $request, UserRepository $userRepository): Response
    {
        $error = '';
        $divVisibility = 'none';

        $nametag = trim($request->request->get('searchNametag', ''));

        $currentUser = $this->getUser();

        $users = [];

        if ($currentUser instanceof \App\Entity\User && $nametag === $currentUser->getNametag()) {
            $error = 'Error: You cannot search yourself.';
            $divVisibility = 'block';
        } elseif ($nametag === '') {
            $error = 'Error: Nametag cannot be empty.';
            $divVisibility = 'block';
        } else {
            $users = $userRepository->findByNametag($nametag);
            if (empty($users)) {
                $error = 'Error: No users found with this nametag.';
                $divVisibility = 'block';
            }
        }

        return $this->render('search/index.html.twig', [
            'nametag' => $nametag,
            'users' => $users,
            'divVisibility' => $divVisibility,
            'error' => $error,
        ]);
    }
}
