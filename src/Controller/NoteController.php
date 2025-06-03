<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Note;
use App\Entity\NoteVote;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function Webmozart\Assert\Tests\StaticAnalysis\boolean;

class NoteController extends AbstractController
{
    #[Route('/note/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em, NotificationService $notificationService,NoteRepository $noteRepository): Response
    {
        $error = '';

        $notes = $noteRepository->findBy([], ['publicationDate' => 'DESC']);

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $mentionedNametag = $request->request->get('nametag');

            $receiver = $em->getRepository(User::class)->findOneBy(['nametag' => $mentionedNametag]);

            if (empty(trim($content)) || empty(trim($mentionedNametag)) || ($mentionedNametag == $this->getUser()->getNametag())) {
                $error = 'Error: Invalid input or you can’t post a note to yourself.';
                return $this->render('default/index.html.twig', [
                    'notifications' => $notificationService->getLatestUserNotifications(),
                    'error' => $error,
                    'divVisibility' => 'block',
                    'notes' => $notes
                ]);
            } else if(!$receiver) {
                $error = 'Error: The nametag you entered does not exist.';
                return $this->render('default/index.html.twig', [
                    'notifications' => $notificationService->getLatestUserNotifications(),
                    'error' => $error,
                    'divVisibility' => 'block',
                    'notes' => $notes
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
            'notifications' => $notificationService->getLatestUserNotifications(),
            'error' => $error,
            'divVisibility' => 'none'
            ]);
    }

    #[Route('/notification/{notificationId}/redirect', name: 'app_note_redirect')]
    public function redirectToNote(int $notificationId, NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notification = $notificationService->notificationRepository->find($notificationId);

        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }

        if ($user) {
            $notificationService->markOneAsRead($user, $notification);
        }

        $note = $notification->getNote();

        return $this->redirectToRoute('app_note_show', ['noteId' => $note->getId()]);
    }

    #[Route('/note/{noteId}', name: 'app_note_show')]
    public function show(int $noteId, NoteRepository $noteRepository, NotificationService $notificationService): Response
    {
        $notifications = $notificationService->getLatestUserNotifications();
        $note = $noteRepository->find($noteId);

        if (!$note) {
            throw $this->createNotFoundException('Note not found');
        }

        $comments = $note->getComments();

        return $this->render('note/index.html.twig', [
            'divVisibility' => 'none',
            'notifications' => $notifications,
            'note' => $note,
            'comments' => $comments
        ]);
    }

    #[Route('/note/{id}/upvote', name: 'note_upvote', methods: ['POST'])]
    public function upvote(Note $note,
                           EntityManagerInterface $em,
                           Request $request,
                           NoteVoteRepository $noteVoteRepository): Response
    {
        $user = $this->getUser();

        $existingVote = $noteVoteRepository->findOneBy([
            'user' => $user,
            'note' => $note,
        ]);

        if (!$existingVote) {
            $vote = new NoteVote();
            $vote->setUser($user);
            $vote->setNote($note);
            $vote->setIsUpvoted(true);
            $vote->setIsDownvoted(false);

            $note->incrementUpVote();

            $em->persist($vote);

        } elseif (!$existingVote->isUpvoted()) {
            $note->incrementUpVote();
            if ($existingVote->isDownvoted()) {
                $note->decrementDownVote();
            }
            $existingVote->setIsUpvoted(true);
            $existingVote->setIsDownvoted(false);
        }

        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/note/{id}/downvote', name: 'note_downvote', methods: ['POST'])]
    public function downvote(Note $note,
                           EntityManagerInterface $em,
                           Request $request,
                           NoteVoteRepository $noteVoteRepository): Response
    {
        $user = $this->getUser();

        $existingVote = $noteVoteRepository->findOneBy([
            'user' => $user,
            'note' => $note,
        ]);

        if (!$existingVote) {
            $vote = new NoteVote();
            $vote->setUser($user);
            $vote->setNote($note);
            $vote->setIsUpvoted(false);
            $vote->setIsDownvoted(true);

            $note->incrementUpVote();

            $em->persist($vote);

        } elseif (!$existingVote->isDownvoted()) {
            $note->incrementDownVote();
            if ($existingVote->isUpvoted()) {
                $note->decrementUpVote();
            }
            $existingVote->setIsUpvoted(false);
            $existingVote->setIsDownvoted(true);
        }

        $em->flush();

        return $this->redirect($request->headers->get('referer'));
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
