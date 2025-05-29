<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Notification;
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

        $notes = $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']);
        $notifications = $em->getRepository(Notification::class)->findBy([], ['id' => 'DESC']);

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'notifications' => $notifications,
            'currentUserNametag' => $this->getUser()->getNametag(),
            'divVisibility' => 'none',
            'error' => $error,
        ]);
    }
}