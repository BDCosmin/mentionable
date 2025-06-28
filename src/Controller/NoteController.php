<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Entity\CommentVote;
use App\Entity\Note;
use App\Entity\NoteReport;
use App\Entity\NoteVote;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\FriendRequestRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\boolean;

class NoteController extends AbstractController
{
    #[Route('/note/new/{ringId?}', name: 'app_note_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(?int $ringId,
                        Request $request,
                        EntityManagerInterface $em,
                        NotificationService $notificationService,
                        NoteRepository $noteRepository,
                        NoteVoteRepository $noteVoteRepository,
                        SluggerInterface $slugger,
                        RingRepository $ringRepository,
                        RingMemberRepository $ringMemberRepository
    ): Response
    {
        $error = '';
        $user = $this->getUser();

        if($ringId){
            $ring = $ringRepository->find($ringId);
            $ringNotes = $noteRepository->findBy(
                ['ring' => $ring, 'isFromRing' => 1],
                ['publicationDate' => 'DESC']
            );
            $members = $ringMemberRepository->findBy(['ring' => $ring]);
            $notes = $noteRepository->findBy(
                ['ring' => $ring, 'isFromRing' => 1],
                ['publicationDate' => 'DESC']
            );
        } else {
            $notes = $noteRepository->findBy([], ['publicationDate' => 'DESC']);
        }
        $noteVotes = $noteVoteRepository->findBy([]);

        foreach ($notes as $note) {
            $note->mentionedUserId = $note->getMentionedUserId($em);
        }

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $mentionedNametag = $request->request->get('nametag');

            $receiver = $em->getRepository(User::class)->findOneBy(['nametag' => $mentionedNametag]);

            if ($ringId) {
                $ring = $ringRepository->find($ringId);

                $member = $ringMemberRepository->findOneBy([
                    'ring' => $ring,
                    'user' => $receiver
                ]);
            }

            if (empty(trim($content)) || empty(trim($mentionedNametag)) || ($mentionedNametag == $this->getUser()->getNametag())) {
                $error = 'Error: Invalid input or you can’t post a note to yourself.';
                if ($ringId) {
                    return $this->render('ring/page.html.twig', [
                        'error' => $error,
                        'divVisibility' => 'block',
                        'notes' => $notes,
                        'noteVotes' => $noteVotes,
                        'votesMap' => $votesMap,
                        'ring' => $ring,
                        'members' => $members,
                        'ringNotes' => $ringNotes
                    ]);
                } else {
                    return $this->render('default/index.html.twig', [
                        'error' => $error,
                        'divVisibility' => 'block',
                        'notes' => $notes,
                        'noteVotes' => $noteVotes,
                        'votesMap' => $votesMap
                    ]);
                }
            } else if($ringId && !$member) {
                $error = 'Error: The person you try to mention is not part of this ring.';
                return $this->render('ring/page.html.twig', [
                    'error' => $error,
                    'divVisibility' => 'block',
                    'notes' => $notes,
                    'noteVotes' => $noteVotes,
                    'votesMap' => $votesMap,
                    'ring' => $ring,
                    'members' => $members,
                    'ringNotes' => $ringNotes
                ]);
            } else if(!$receiver) {
                $error = 'Error: The nametag you entered does not exist.';
                if ($ringId) {
                    return $this->render('ring/page.html.twig', [
                        'error' => $error,
                        'divVisibility' => 'block',
                        'notes' => $notes,
                        'noteVotes' => $noteVotes,
                        'votesMap' => $votesMap,
                        'ring' => $ring,
                        'members' => $members,
                        'ringNotes' => $ringNotes
                    ]);
                } else {
                    return $this->render('default/index.html.twig', [
                        'error' => $error,
                        'divVisibility' => 'block',
                        'notes' => $notes,
                        'noteVotes' => $noteVotes,
                        'votesMap' => $votesMap
                    ]);
                }
            } else {
                $note = new Note();
                $note->setUser($this->getUser());
                $note->setContent($content);
                $note->setIsEdited(false);
                $note->setNametag($mentionedNametag);
                $note->setPublicationDate(new \DateTime());
                if ($ringId) {
                    $note->setRing($ring);
                    $note->setIsFromRing(true);
                }

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

                if ($ringId) {
                    return $this->redirectToRoute('app_ring_show', ['id' => $ringId]);
                } else {
                    return $this->redirectToRoute('homepage');
                }
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/note/{id}/delete', name: 'app_note_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id ,Request $request, EntityManagerInterface $em, NoteRepository $noteRepository): Response
    {
        $note = $noteRepository->find($id);

        $em->remove($note);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/note/{id}/update/{ringId?}', name: 'app_note_update', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Note $note,
                           NoteVoteRepository $noteVoteRepository,
                           EntityManagerInterface $em,
                           SluggerInterface $slugger,
                           ?int $ringId,
                           RingRepository $ringRepository
    ): Response
    {
        $error = '';
        $divVisibility = 'none';
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $noteVotes = $noteVoteRepository->findBy([]);

        $note->mentionedUserId = $note->getMentionedUserId($em);

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

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

                    if ($ringId) {
                        return $this->redirectToRoute('app_ring_show', ['id' => $ringId]);
                    } else {
                        return $this->redirectToRoute('app_note_show', ['noteId' => $note->getId()]);
                    }
                }
            }
        }

        return $this->render('note/update.html.twig', [
            'error' => $error,
            'divVisibility' => $divVisibility,
            'note' => $note,
            'noteVotes' => $noteVotes,
            'votesMap' => $votesMap,
        ]);
    }

    #[Route('/note/{noteId}', name: 'app_note_show')]
    public function show(int $noteId, NoteRepository $noteRepository,NoteVoteRepository $noteVoteRepository,EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $note = $noteRepository->find($noteId);

        $note->mentionedUserId = $note->getMentionedUserId($em);

        if (!$note) {
            return $this->redirectToRoute('homepage');
        }

        $votesMap = [];
        if ($user) {
            $noteVote = $noteVoteRepository->findOneByUserAndNote($user, $note);
            if ($noteVote) {
                if ($noteVote->isUpvoted()) {
                    $votesMap[$note->getId()] = 'upvote';
                } elseif ($noteVote->isDownvoted()) {
                    $votesMap[$note->getId()] = 'downvote';
                }
            }
        }

        $comments = $note->getComments();

        return $this->render('note/index.html.twig', [
            'divVisibility' => 'none',
            'note' => $note,
            'comments' => $comments,
            'votesMap' => $votesMap,
        ]);
    }

    #[Route('/note/{id}/report', name: 'app_note_report', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function report(int $id ,Request $request, EntityManagerInterface $em, NoteRepository $noteRepository, NotificationService $notificationService): Response
    {
        $note = $noteRepository->find($id);

        if ($request->isMethod('POST')) {
            $reason = $request->request->get('reason');

            if (!$reason) {
                $this->addFlash('error', 'Please select at least one reason.');
                return $this->redirectToRoute('app_note_report', ['id' => $note->getId()]);
            }

            $noteReport = new NoteReport();
            $noteReport->setNote($note);
            $noteReport->setType($reason);

            $notification = new Notification();
            $notification->setNote($note);
            $notification->setIsRead(false);
            $notification->setType('reported');
            $notification->setSender($this->getUser());
            $notification->setReceiver($this->getUser());
            $notification->setNotifiedDate(new \DateTime());

            $em->persist($noteReport);
            $em->persist($notification);

            $em->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('note/report.html.twig', [
            'note' => $note,
            'formActionRoute' => 'app_note_report',
            'routeParams' => ['id' => $id]
        ]);
    }

    #[Route('/note/{id}/upvote', name: 'note_upvote', methods: ['POST'])]
    public function upvote(Note $note,
                           EntityManagerInterface $em,
                           Request $request,
                           NoteVoteRepository $noteVoteRepository): JsonResponse
    {
        $user = $this->getUser();
        $existingVote = $noteVoteRepository->findOneBy([
            'user' => $user,
            'note' => $note,
        ]);

        $shouldRemoveVote = false;

        if (!$existingVote) {
            $vote = new NoteVote();
            $vote->setUser($user);
            $vote->setNote($note);
            $vote->setIsUpvoted(true);
            $vote->setIsDownvoted(false);
            $note->incrementUpVote();
            $em->persist($vote);

        } else {
            if ($existingVote->isUpvoted() && !$existingVote->isDownvoted()) {
                $existingVote->setIsUpvoted(false);
                $note->decrementUpVote();
                $shouldRemoveVote = true;

            } elseif ($existingVote->isDownvoted() && !$existingVote->isUpvoted()) {
                $existingVote->setIsDownvoted(false);
                $existingVote->setIsUpvoted(true);
                $note->decrementDownVote();
                $note->incrementUpVote();
            }
        }

        // Important: verificăm flagul, nu recitim proprietățile
        if ($shouldRemoveVote && $existingVote) {
            $em->remove($existingVote);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'upvotes' => $note->getUpVote(),
            'downvotes' => $note->getDownVote(),
        ]);
    }

    #[Route('/note/{id}/downvote', name: 'note_downvote', methods: ['POST'])]
    public function downvote(Note $note,
                             EntityManagerInterface $em,
                             Request $request,
                             NoteVoteRepository $noteVoteRepository): JsonResponse
    {
        $user = $this->getUser();

        $existingVote = $noteVoteRepository->findOneBy([
            'user' => $user,
            'note' => $note,
        ]);

        $shouldRemoveVote = false;

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

        if ($shouldRemoveVote && $existingVote) {
            $em->remove($existingVote);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'upvotes' => $note->getUpVote(),
            'downvotes' => $note->getDownVote(),
        ]);
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

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('comment/partial.html.twig', [
                'comment' => $comment,
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html,
            ]);
        }

        return $this->redirectToRoute('homepage');
    }

    #[Route('post/comment/{noteId}-{id}/delete', name: 'note_comment_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteComment(int $id ,Request $request, EntityManagerInterface $em, CommentRepository $commentRepository): Response
    {

        $comment = $commentRepository->find($id);

        if (!$comment) {
            return new JsonResponse(['success' => false, 'message' => 'Comentariu inexistent'], 404);
        }

        $em->remove($comment);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('post/comment/{noteId}-{id}/update', name: 'note_comment_update', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateComment(Request $request, int $id, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $error = '';
        $divVisibility = 'none';

        $comment = $em->getRepository(Comment::class)->find($id);
        $note = $comment->getNote();
        $note->mentionedUserId = $note->getMentionedUserId($em);

        if (!$comment || !$comment->getNote()) {
            return $this->redirectToRoute('homepage');
        } else {
            $note = $comment->getNote();
            if ($request->isMethod('POST')) {
                $newMessage = trim((string)$request->request->get('message'));
                $currentMessage = trim((string)$comment->getMessage());

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
                'note' => $note,
                'noteId' => $note->getId()
            ]);
        }
    }

    #[Route('note/comment/{noteId}-{id}/report', name: 'note_comment_report', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function reportComment(int $id, int $noteId, Request $request, EntityManagerInterface $em, CommentRepository $commentRepository, NoteRepository $noteRepository, NotificationService $notificationService): Response
    {

        $note = $noteRepository->find($noteId);
        $comment = $commentRepository->find($id);

        if ($request->isMethod('POST')) {
            $reason = $request->request->get('reason');

            if (!$reason) {
                $this->addFlash('error', 'Please select at least one reason.');
                return $this->redirectToRoute('note_comment_report', ['id' => $id, 'noteId' => $noteId]);
            }

            $commentReport = new CommentReport();
            $commentReport->setComment($comment);
            $commentReport->setType($reason);

            $notification = new Notification();
            $notification->setNote($note);
            $notification->setComment($comment);
            $notification->setType('reported');
            $notification->setSender($this->getUser());
            $notification->setReceiver($this->getUser());
            $notification->setNotifiedDate(new \DateTime());
            $notification->setIsRead(false);

            $em->persist($commentReport);
            $em->persist($notification);

            $em->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('note/report.html.twig', [
            'note' => $note,
            'formActionRoute' => 'note_comment_report',
            'routeParams' => ['id' => $id, 'noteId' => $noteId]
        ]);
    }

    #[Route('note/comment/{noteId}-{id}/upvote', name: 'note_comment_upvote', methods: ['POST'])]
    public function upvoteComment(int $id, int $noteId,
                           Comment $comment,
                           EntityManagerInterface $em,
                           Request $request,
                           NoteVoteRepository $noteVoteRepository,
                           CommentVoteRepository $commentVoteRepository,
    ): Response
    {
        $user = $this->getUser();
        $note = $comment->getNote();

        $existingVote = $commentVoteRepository->findOneBy([
            'user' => $user,
            'comment' => $comment,
            'note' => $note
        ]);

        if (!$existingVote) {
            $vote = new CommentVote();
            $vote->setUser($user);
            $vote->setNote($note);
            $vote->setComment($comment);
            $vote->setIsUpvoted(true);

            $comment->incrementUpVote();
            $em->persist($vote);

        } else {
            if ($existingVote->isUpvoted()) {
                $comment->decrementUpVote();
                $em->remove($existingVote);
            } else {
                $existingVote->setIsUpvoted(true);
                $comment->incrementUpVote();
                $em->persist($existingVote);
            }
        }
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'upvotes' => $comment->getUpVote(),
            ]);
        }

        return $this->redirect($request->headers->get('referer'));
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
