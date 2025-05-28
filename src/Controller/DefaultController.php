<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }


        $user = $this->getUser();
        $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
        ]);
    }
}