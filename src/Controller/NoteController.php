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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\boolean;

class NoteController extends AbstractController
{
    #[Route('/note/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request,
                        EntityManagerInterface $em,
                        NotificationService $notificationService,
                        NoteRepository $noteRepository,
                        NoteVoteRepository $noteVoteRepository,
                        SluggerInterface $slugger,
    ): Response
    {
        $error = '';

        $notes = $noteRepository->findBy([], ['publicationDate' => 'DESC']);
        $noteVotes = $noteVoteRepository->findBy([]);

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
                    'notes' => $notes,
                    'noteVotes' => $noteVotes
                ]);
            } else if(!$receiver) {
                $error = 'Error: The nametag you entered does not exist.';
                return $this->render('default/index.html.twig', [
                    'notifications' => $notificationService->getLatestUserNotifications(),
                    'error' => $error,
                    'divVisibility' => 'block',
                    'notes' => $notes,
                    'noteVotes' => $noteVotes
                ]);
            } else {
                $note = new Note();
                $note->setUser($this->getUser());
                $note->setContent($content);
                $note->setIsEdited(false);
                $note->setNametag($mentionedNametag);
                $note->setPublicationDate(new \DateTime());

                $noteImageFile = $request->files->get('image');
                if ($noteImageFile) {
                    $originalFilename = pathinfo($noteImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $noteImageFile->guessExtension();

                    $noteImageFile->move(
                        $this->getParameter('notes_directory'),
                        $newFilename
                    );


                    $note->setImage($newFilename);

                }

                $em->persist($note);
                $em->flush();

                $notificationService->notifyNote($this->getUser(), $receiver, $note);

                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('default/index.html.twig', [
            'notifications' => $notificationService->getLatestUserNotifications(),
            'error' => $error,
            'divVisibility' => 'none',
            'noteVotes' => $noteVotes
            ]);
    }

    #[Route('/note/{id}/update', name: 'app_note_update', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Note $note, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $error = '';
        $divVisibility = 'none';

        if ($request->isMethod('POST')) {
            $newContent = trim((string) $request->request->get('content'));
            $currentContent = trim((string) $note->getContent());
            $newImageFile = $request->files->get('image');

            $isContentInvalid = empty($newContent) || $newContent === $currentContent;
            $isImageInvalid = !$newImageFile instanceof UploadedFile;

            if ($isContentInvalid && $isImageInvalid) {
                $error = 'Error: Invalid content and/or image.';
                $divVisibility = 'block';
            } else {

                if (!$isContentInvalid) {
                    $note->setContent($newContent);
                }

                if (!$isImageInvalid) {
                    $originalFilename = pathinfo($newImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $newImageFile->guessExtension();

                    try {
                        $newImageFile->move(
                            $this->getParameter('notes_directory'),
                            $newFilename
                        );
                        $note->setImage($newFilename);
                    } catch (FileException $e) {
                        $error = 'Error: Failed to upload image.';
                        $divVisibility = 'block';
                    }
                }

                if (empty($error)) {
                    $note->setIsEdited(true);
                    $note->setPublicationDate(new \DateTime());

                    $em->persist($note);
                    $em->flush();

                    return $this->redirectToRoute('app_note_show', ['noteId' => $note->getId()]);
                }
            }
        }

        return $this->render('note/update.html.twig', [
            'error' => $error,
            'divVisibility' => $divVisibility,
            'note' => $note,
        ]);
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

        } else {
            if ($existingVote->isUpvoted() && !($existingVote->isDownvoted())) {

                $existingVote->setIsUpvoted(false);
                $note->decrementUpVote();

            } elseif ($existingVote->isDownvoted() && !($existingVote->isUpvoted())) {

                $existingVote->setIsDownvoted(false);
                $existingVote->setIsUpvoted(true);
                $note->decrementDownVote();
                $note->incrementUpVote();

            }
        }

        if ($existingVote && !$existingVote->isUpvoted() && !$existingVote->isDownvoted()) {
            $em->remove($existingVote);
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

            $note->incrementDownVote();
            $em->persist($vote);

        } else {
            if ($existingVote->isDownvoted() && !($existingVote->isUpvoted())) {

                $existingVote->setIsDownvoted(false);
                $note->decrementDownVote();

            } elseif ($existingVote->isUpvoted() && !($existingVote->isDownvoted())) {

                $existingVote->setIsDownvoted(true);
                $existingVote->setIsUpvoted(false);
                $note->decrementUpVote();
                $note->incrementDownVote();

            }
        }

        if ($existingVote && !$existingVote->isUpvoted() && !$existingVote->isDownvoted()) {
            $em->remove($existingVote);
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
        $comment->setPublicationDate(new \DateTime());
        $comment->setNote($note);

        $em->persist($comment);
        $em->flush();

        $receiver = $note->getUser();

        $notificationService->notifyComment($this->getUser(), $receiver, $comment);

        return $this->redirectToRoute('homepage');
    }

    #[Route('post/comment/{id}-{noteId}/update', name: 'app_comment_update', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateComment(Request $request, Comment $comment, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $error = '';
        $divVisibility = 'none';

        if ($request->isMethod('POST')) {
            $newMessage = trim((string) $request->request->get('message'));
            $currentMessage = trim((string) $comment->getMessage());

            $isMessageInvalid = empty($newMessage) || $newMessage === $currentMessage;

            if ($isMessageInvalid) {
                $error = 'Error: Invalid content.';
                $divVisibility = 'block';
            } else {

                $comment->setMessage($newMessage);
                $comment->setIsEdited(true);
                $comment->setPublicationDate(new \DateTime());

                $em->persist($comment);
                $em->flush();

                return $this->redirectToRoute('app_note_show', ['noteId' => $comment->getNote()->getId()]);
            }
        }

        return $this->render('note/comment_update.html.twig', [
            'error' => $error,
            'divVisibility' => $divVisibility,
            'comment' => $comment,
            'note' => $comment->getNote(),
            'noteId' => $comment->getNote()->getId()
        ]);
    }

    #[Route('/notification/{notificationId}/redirect', name: 'app_note_redirect')]
    public function redirectToNotification(int $notificationId, NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notification = $notificationService->notificationRepository->find($notificationId);

        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }

        if ($user) {
            $notificationService->markOneAsRead($user, $notification);
        }

        if ($notification->getNote()) {
            return $this->redirectToRoute('app_note_show', ['noteId' => $notification->getNote()->getId()]);
        }

        if ($notification->getComment()) {
            return $this->redirectToRoute('app_note_show', [
                'noteId' => $notification->getComment()->getNote()->getId()
            ]);
        }

        if ($notification->getType() === 'friend_request') {
            return $this->redirectToRoute('app_profile', [
                'nametag' => $user->getNametag()
            ]);
        }

        if ($notification->getLink()) {
            return $this->redirect($notification->getLink());
        }

        $this->addFlash('error', 'Notificarea nu are un link valid.');
        return $this->redirectToRoute('homepage');
    }
}
