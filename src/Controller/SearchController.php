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
        // Preia nametag-ul din cererea POST
        $nametag = trim($request->request->get('searchNametag', ''));

        // Inițializează variabila pentru rezultate
        $users = [];

        // Caută utilizatori doar dacă nametag-ul nu este gol
        if ($nametag !== '') {
            // Caută utilizatori cu nametag exact sau similar
            $users = $userRepository->findByNametag($nametag);
        }

        // Renderizează șablonul cu rezultatele și nametag-ul căutat
        return $this->render('search/index.html.twig', [
            'nametag' => $nametag,
            'users' => $users,
            'divVisibility' => 'none',
            'error' => $error,
        ]);
    }
}
