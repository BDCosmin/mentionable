<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Webmozart\Assert\Tests\StaticAnalysis\boolean;

class NoteController extends AbstractController
{
    #[Route('/note/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, NotificationService $notificationService): Response
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
                    'divVisibility' => 'block'
                ]);
            } else if(!$receiver) {
                $error = 'Error: The nametag you entered does not exist.';
                return $this->render('default/index.html.twig', [
                    'error' => $error,
                    'divVisibility' => 'block'
                ]);
            } else {
                $note = new Note();
                $note->setUser($this->getUser());
                $note->setContent($content);
                $note->setNametag($mentionedNametag);
                $note->setPublicationDate(new \DateTime());

                $em->persist($note);
                $em->flush();

                $notificationService->notifyNote($this->getUser(), $receiver, $note);

                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('default/index.html.twig', [
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
    public function comment(Note $note, Request $request, EntityManagerInterface $em, NotificationService $notificationService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $comment = new Comment();
        $comment->setUser($this->getUser());
        $comment->setMessage($request->request->get('message'));
        $comment->setNote($note);

        $em->persist($comment);
        $em->flush();

        $receiver = $note->getUser();

        $notificationService->notifyComment($this->getUser(), $receiver, $comment);

        return $this->redirectToRoute('homepage');
    }
}
