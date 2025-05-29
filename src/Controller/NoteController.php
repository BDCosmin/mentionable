<?php

namespace App\Controller;

use App\Entity\Comment;
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
        $error = '';
        $user = $this->getUser();
        $currentUserNametag = $this->getUser()->getNametag();

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $nametag = $request->request->get('nametag');

            $userMentioned = $em->getRepository(User::class)->findOneBy(['nametag' => $nametag]);

            if (empty(trim($content)) || empty(trim($nametag)) || ($nametag == $currentUserNametag)) {
                $error = 'Error: Invalid input or you can’t post a note to yourself.';
                return $this->render('default/index.html.twig', [
                    'error' => $error,
                    'notes' => $em->getRepository(Note::class)->findBy([], ['id' => 'DESC']),
                    'currentUserNametag' => $currentUserNametag,
                    'divVisibility' => 'block'
                ]);
            } else if(!$userMentioned) {
                $error = 'Error: The nametag you entered does not exist.';
                return $this->render('default/index.html.twig', [
                    'error' => $error,
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

        $comment = new Comment();
        $comment->setNote($note);
        $comment->setUser($this->getUser());
        $comment->setMessage($request->request->get('message'));

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }


}
