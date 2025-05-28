<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends AbstractController
{
    #[Route('/note/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $currentUserNametag = $this->getUser()->getNametag();

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $nametag = $request->request->get('nametag');

            if (empty(trim($content)) || empty(trim($nametag)) || ($nametag == $currentUserNametag)) {
                return $this->render('default/index.html.twig', [
                    'errorMentionToYourself' => 'Error: Invalid input or you can’t post a note to yourself.',
                    'notes' => $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']),
                    'currentUserNametag' => $currentUserNametag,
                    'divVisibility' => 'block'
                ]);
            } else {

                $note = new Note();
                $note->setContent($content);
                $note->setNametag($nametag);
                $note->setUser($user);
                $author = $note->getUser()->getNametag();
                $avatar = $note->getUser()->getAvatar();

                $em->persist($note);
                $em->flush();

                return $this->redirectToRoute('app_note_new');

            }

        }

        $notes = $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']);

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'currentUserNametag' => $currentUserNametag,
            'divVisibility' => 'none'
            ]);
    }
}
