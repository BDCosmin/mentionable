<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Note;
use App\Entity\Notification;
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
        $error = '';

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $mentionedNametag = $request->request->get('nametag');

            $receiver = $em->getRepository(User::class)->findOneBy(['nametag' => $mentionedNametag]);

            if (empty(trim($content)) || empty(trim($mentionedNametag)) || ($mentionedNametag == $this->getUser()->getNametag())) {
                $error = 'Error: Invalid input or you can’t post a note to yourself.';
                return $this->render('default/index.html.twig', [
                    'error' => $error,
                    'notes' => $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']),
                    'divVisibility' => 'block'
                ]);
            } else if(!$receiver) {
                $error = 'Error: The nametag you entered does not exist.';
                return $this->render('default/index.html.twig', [
                    'error' => $error,
                    'notes' => $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']),
                    'divVisibility' => 'block'
                ]);
            } else {
                $note = new Note();
                $note->setUser($this->getUser());
                $note->setContent($content);
                $note->setNametag($mentionedNametag);

                $em->persist($note);
                $em->flush();

                $notification = new Notification();
                $notification->setNote($note);
                $notification->setSender($this->getUser());
                $notification->setReceiver($receiver);
                $notification->setType('mentioned');
                $notification->setNotifiedDate(new \DateTime());

                $em->persist($notification);
                $em->flush();

                $notification->getHumanTime();

                return $this->redirectToRoute('homepage');
            }
        }

        $notes = $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']);
        $notifications = $em->getRepository(Notification::class)->findBy([], ['id' => 'DESC']);

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'notifications' => $notifications,
            'error' => $error,
            'divVisibility' => 'none'
            ]);
    }

    #[Route('/note/{id}/upvote', name: 'note_upvote', methods: ['POST'])]
    public function upvote(Note $note, EntityManagerInterface $em): Response
    {
        $note->incrementUpVote();
        $em->flush();

        return $this->redirectToRoute('homepage');
    }

    #[Route('/note/{id}/downvote', name: 'note_downvote', methods: ['POST'])]
    public function downvote(Note $note, EntityManagerInterface $em): Response
    {
        $note->incrementDownVote();
        $em->flush();

        return $this->redirectToRoute('homepage');
    }

    #[Route('/note/{id}/comment', name: 'note_comment', methods: ['POST'])]
    public function comment(Note $note, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $sender = $this->getUser();

        $comment = new Comment();
        $comment->setNote($note);
        $comment->setUser($this->getUser());
        $comment->setMessage($request->request->get('message'));

        $receiver = $note->getUser();

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }


}
