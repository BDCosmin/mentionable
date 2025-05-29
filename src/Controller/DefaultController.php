<?php

namespace App\Controller;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $em): Response
    {
        $error = '';
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $currentUserNametag = $this->getUser()->getNametag();

        $notes = $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']);

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'currentUserNametag' => $currentUserNametag,
            'divVisibility' => 'none',
            'error' => $error,
        ]);
    }
}