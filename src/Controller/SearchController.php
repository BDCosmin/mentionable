<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_find_nametag', methods: ['GET', 'POST'])]
    public function search(Request $request, UserRepository $userRepository): Response
    {
        $error = '';
        $divVisibility = 'none';

        $nametag = trim($request->get('nametag') ?? $request->request->get('searchNametag', ''));

        $currentUser = $this->getUser();
        $users = [];

        if ($nametag !== '') {
            if ($currentUser instanceof \App\Entity\User && $nametag === $currentUser->getNametag()) {
                $error = 'Error: You cannot search yourself.';
                $divVisibility = 'block';
            } else {
                $users = $userRepository->findByNametag($nametag);
                if (empty($users)) {
                    $error = 'Error: No users found with this nametag.';
                    $divVisibility = 'block';
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'nametag' => $nametag,
            'users' => $users,
            'divVisibility' => $divVisibility,
            'error' => $error,
        ]);
    }

    #[Route('/nametag-suggestions', name: 'app_nametag_suggestions', methods: ['GET'])]
    public function nametagSuggestions(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('query', '');

        if (strlen($query) < 2) {
            return new JsonResponse([]); // Nu returna sugestii dacă sunt mai puțin de 2 litere
        }

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.nametag LIKE :query')
            ->setParameter('query', $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $nametags = array_map(fn($user) => $user->getNametag(), $users);

        return new JsonResponse($nametags);
    }

}
