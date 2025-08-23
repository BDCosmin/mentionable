<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Entity\CommentVote;
use App\Entity\Note;
use App\Entity\NoteReport;
use App\Entity\NoteVote;
use App\Entity\Notification;
use App\Entity\RingMembers;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\FriendRequestRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\TextModerationService;
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

/**
 * @method getDoctrine()
 */
class NoteController extends AbstractController
{
    #[Route('/note/new/{ringId?}', name: 'app_note_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        ?int $ringId,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService,
        NoteRepository $noteRepository,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        SluggerInterface $slugger,
        RingRepository $ringRepository,
        RingMemberRepository $ringMemberRepository,
        TextModerationService $moderator
    ): Response {
        $user = $this->getUser();
        $error = '';

        $ring = null;
        $ringNotes = [];
        $members = [];

        if ($ringId) {
            $ring = $ringRepository->find($ringId);
            $ringNotes = $noteRepository->findBy(['ring' => $ring, 'isFromRing' => true], ['publicationDate' => 'DESC']);
            $members = $ringMemberRepository->findBy(['ring' => $ring]);
            $notes = $ringNotes;
        } else {
            $notes = $noteRepository->findBy([], ['publicationDate' => 'DESC']);
        }

        $noteVotes = $noteVoteRepository->findBy([]);
        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                $votesMap[$vote->getNote()->getId()] = $vote->isUpvoted() ? 'upvote' : 'downvote';
            }
        }

        $commentVotes = $commentVoteRepository->findBy(['user' => $user]);
        $commentVotesMap = [];

        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

        $favoritesMap = [];
        foreach ($notes as $note) {
            $favoritesMap[$note->getId()] = $user->hasFavorite($note);
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');

            // Perspective API
            $moderation = $moderator->analyze($content);
            $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
            $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

            if ($toxicityScore > 0.85 || $insultScore > 0.85) {
                $error = 'Your note was rejected because it may be toxic or insulting.';

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['status' => 'error', 'message' => $error], 400);
                }

                $template = $ringId ? 'ring/page.html.twig' : 'default/index.html.twig';

                return $this->render($template, [
                    'error' => $error,
                    'divVisibility' => 'block',
                    'notes' => $notes,
                    'noteVotes' => $noteVotes,
                    'votesMap' => $votesMap,
                    'commentVotesMap' => $commentVotesMap,
                    'favoritesMap' => $favoritesMap,
                    'ring' => $ring,
                    'members' => $members,
                    'ringNotes' => $ringNotes,
                ]);
            }

            $fileName = '';
            $noteImageFile = $request->files->get('image');
            if ($noteImageFile) {
                $originalFilename = pathinfo($noteImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $noteImageFile->guessExtension();

                $noteImageFile->move($this->getParameter('notes_directory'), $newFilename);
                $fileName = $newFilename;
            }

            // Verify if posted from the 'mentionable' account
            if ($user->getId() != 24)
            {
                $mentionedNametag = $request->request->get('nametag');
                $receiver = $em->getRepository(User::class)->findOneBy(['nametag' => $mentionedNametag]);

                $member = null;
                if ($ringId && $receiver) {
                    $member = $ringMemberRepository->findOneBy(['ring' => $ring, 'user' => $receiver]);
                }

                if (empty(trim($content)) || empty(trim($mentionedNametag)) || $mentionedNametag === $user->getNametag()) {
                    $error = 'Error: Invalid input or you can’t post a note to yourself.';
                } elseif ($ringId && !$member) {
                    $error = 'Error: The person you try to mention is not part of this ring.';
                } elseif (!$receiver) {
                    $error = 'Error: The nametag you entered does not exist.';
                }

                if ($error !== '') {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['status' => 'error', 'message' => $error], 400);
                    }

                    $template = $ringId ? 'ring/page.html.twig' : 'default/index.html.twig';
                    return $this->render($template, [
                        'error' => $error,
                        'divVisibility' => 'block',
                        'notes' => $notes,
                        'noteVotes' => $noteVotes,
                        'votesMap' => $votesMap,
                        'commentVotesMap' => $commentVotesMap,
                        'favoritesMap' => $favoritesMap,
                        'ring' => $ring,
                        'members' => $members,
                        'ringNotes' => $ringNotes,
                    ]);
                }

                $note = new Note();
                $note->setUser($user);
                $note->setContent($content);
                $note->setNametag($mentionedNametag);
                $note->setIsEdited(false);
                $note->setPublicationDate(new \DateTime());
                $note->setImage($fileName);

                if ($ringId) {
                    $note->setRing($ring);
                    $note->setIsFromRing(true);
                }

                $note->setMentionedUser($receiver);

                $em->persist($note);
                $em->flush();

                $notificationService->notifyNote($user, $receiver, $note);

            } else {
                $mentionedNametag = 'NULL';

                $note = new Note();
                $note->setUser($user);
                $note->setContent($content);
                $note->setNametag($mentionedNametag);
                $note->setIsEdited(false);
                $note->setPublicationDate(new \DateTime());
                $note->setImage($fileName);

                $em->persist($note);
                $em->flush();

            }

            if ($request->isXmlHttpRequest()) {
                $favoritesMap = [$note->getId() => $user->hasFavorite($note)];

                $html = $this->renderView('note/_note_partial.html.twig', ['note' => $note, 'favoritesMap' => $favoritesMap]);

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Note created successfully',
                    'noteHtml' => $html
                ]);
            }

            return $ringId
                ? $this->redirectToRoute('app_ring_show', ['ringId' => $ringId])
                : $this->redirectToRoute('homepage');
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
    public function update(
        Request $request,
        Note $note,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UserRepository $userRepository,
        RingRepository $ringRepository,
        NoteVoteRepository $noteVoteRepository,
        TextModerationService $moderator,
        ?int $ringId = null
    ): Response
    {
        $error = '';
        $divVisibility = 'none';
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($note->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to edit this note');
        }

        if ($ringId) {
            $ring = $ringRepository->find($ringId);
            if (!$ring) {
                throw $this->createNotFoundException('Ring not found');
            }
        }

        $mentionedUser = $note->getMentionedUser();

        $votesMap = [];
        $vote = $noteVoteRepository->findOneBy(['note' => $note, 'user' => $user]);
        if ($vote) {
            $votesMap[$note->getId()] = $vote->isUpvoted() ? 'upvote' : 'downvote';
        } else {
            $votesMap[$note->getId()] = null;
        }

        if ($request->isMethod('POST')) {
            $newContent = trim((string)$request->request->get('content'));
            $currentContent = trim((string)$note->getContent());
            $newImageFile = $request->files->get('image');
            $nametag = trim((string)$request->request->get('nametag'));

            $moderation = $moderator->analyze($newContent);
            $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
            $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

            if ($toxicityScore > 0.85 || $insultScore > 0.85) {
                $error = 'Your note update was rejected because it may be toxic or insulting.';
                $divVisibility = 'block';

                return $this->render('note/update.html.twig', [
                    'error' => $error,
                    'divVisibility' => $divVisibility,
                    'note' => $note,
                    'mentionedUser' => $note->getMentionedUser(),
                    'ringId' => $ringId,
                    'votesMap' => [],
                ]);
            }

            $isContentInvalid = empty($newContent) || $newContent === $currentContent;
            $isImageInvalid = !$newImageFile instanceof UploadedFile;

            if ($nametag) {
                $mentionedUser = $userRepository->findOneBy(['nametag' => ltrim($nametag, '@')]);
                if ($mentionedUser) {
                    $note->setMentionedUser($mentionedUser);
                    $em->persist($note);
                } else {
                    $error = 'Error: User not found.';
                    $divVisibility = 'block';
                }
            } else {
                $note->setMentionedUser(null);
                $em->persist($note);
            }

            if (!$error) {
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
                    }

                    return $this->redirectToRoute('app_note_show', ['noteId' => $note->getId()]);
                }
            }
        }

        return $this->render('note/update.html.twig', [
            'error' => $error,
            'divVisibility' => $divVisibility,
            'note' => $note,
            'mentionedUser' => $mentionedUser,
            'ringId' => $ringId,
            'votesMap' => $votesMap,
        ]);
    }

    #[Route('/note/{noteId}', name: 'app_note_show')]
    public function show(
        int $noteId,
        NoteRepository $noteRepository,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        RingMemberRepository $ringMemberRepository,
    ): Response
    {
        $user = $this->getUser();

        $note = $noteRepository->find($noteId);

        if (!$note) {
            return $this->redirectToRoute('homepage');
        }

        $role = null;
        $ring = $note->getRing();
        $author = $note->getUser();

        if ($ring && $author) {
            $member = $ringMemberRepository->findOneBy([
                'ring' => $ring,
                'user' => $author,
            ]);

            if ($member) {
                $role = $member->getRole();
            }
        }

        $rolesMap = [];
        if ($ring && $author) {
            $member = $ringMemberRepository->findOneBy([
                'ring' => $ring,
                'user' => $author,
            ]);

            if ($member) {
                $rolesMap[$author->getId()] = $member->getRole();
            }
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

        $commentVotes = $commentVoteRepository->findBy(['user' => $user]);
        $commentVotesMap = [];
        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

        $favoritesMap = [];
        if ($user) {
            $favoritesMap[$note->getId()] = $user->hasFavorite($note);
        }

        $comments = $note->getComments();
        $mentionedUser = $note->getMentionedUser();

        return $this->render('note/index.html.twig', [
            'divVisibility' => 'none',
            'note' => $note,
            'comments' => $comments,
            'votesMap' => $votesMap,
            'commentVotesMap' => $commentVotesMap,
            'mentionedUser' => $mentionedUser,
            'role' => $role,
            'favoritesMap' => $favoritesMap,
            'rolesMap' => $rolesMap,
            'currentUserNametag' => $user->getNametag(),
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
            $noteReport->setStatus('pending');
            $noteReport->setCreationDate(new \DateTime());
            $noteReport->setReporterId($this->getUser()->getId());

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
                           NoteVoteRepository $noteVoteRepository): JsonResponse
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
            if ($existingVote->isUpvoted()) {
                $note->decrementUpVote();
                $em->remove($existingVote);
            } elseif ($existingVote->isDownvoted()) {
                $existingVote->setIsDownvoted(false);
                $existingVote->setIsUpvoted(true);
                $note->decrementDownVote();
                $note->incrementUpVote();
            }
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
                             NoteVoteRepository $noteVoteRepository): JsonResponse
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
            $vote->setIsDownvoted(true);
            $vote->setIsUpvoted(false);
            $note->incrementDownVote();
            $em->persist($vote);
        } else {
            if ($existingVote->isDownvoted()) {
                $note->decrementDownVote();
                $em->remove($existingVote);
            } elseif ($existingVote->isUpvoted()) {
                $existingVote->setIsUpvoted(false);
                $existingVote->setIsDownvoted(true);
                $note->decrementUpVote();
                $note->incrementDownVote();
            }
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'upvotes' => $note->getUpVote(),
            'downvotes' => $note->getDownVote(),
        ]);
    }

    #[Route('/note/{id}/comment', name: 'note_comment', methods: ['POST'])]
    public function comment(Note $note,
                            Request $request,
                            EntityManagerInterface $em,
                            CommentVoteRepository $commentVoteRepository,
                            NotificationService $notificationService,
                            TextModerationService $moderator
    ): Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $message = $request->request->get('message');

        $moderation = $moderator->analyze($message);
        $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
        $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

        if ($toxicityScore > 0.85 || $insultScore > 0.85) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => 'error', 'message' => 'Your comment was rejected because it may be toxic or insulting.'], 400);
            }

            $this->addFlash('danger', 'Your comment was rejected because it may be toxic or insulting.');
            return $this->redirectToRoute('homepage');
        }

        $comment = new Comment();
        $comment->setUser($this->getUser());
        $comment->setMessage($request->request->get('message'));
        $comment->setPublicationDate(new \DateTime());
        $comment->setNote($note);

        $em->persist($comment);
        $em->flush();

        $receiver = $note->getUser();

        $notificationService->notifyComment($user, $receiver, $comment);

        if ($request->isXmlHttpRequest()) {
            $commentVotes = $commentVoteRepository->findBy(['user' => $user]);
            $commentVotesMap = [];

            foreach ($commentVotes as $vote) {
                if ($vote->isUpvoted()) {
                    $commentVotesMap[$vote->getComment()->getId()] = 'upvote';
                }
            }

            $html = $this->renderView('comment/partial.html.twig', [
                'comment' => $comment,
                'commentVotesMap' => $commentVotesMap,
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
    public function updateComment(Request $request,
                                  int $id,
                                  EntityManagerInterface $em,
                                  SluggerInterface $slugger,
                                  TextModerationService $moderator
    ): Response
    {
        $error = '';
        $divVisibility = 'none';

        $comment = $em->getRepository(Comment::class)->find($id);

        if (!$comment || !$comment->getNote()) {
            return $this->redirectToRoute('homepage');
        }

        $note = $comment->getNote();

        $mentionedUser = $note->getMentionedUser();

        if ($request->isMethod('POST')) {
            $newMessage = trim((string) $request->request->get('message'));
            $currentMessage = trim((string) $comment->getMessage());

            $moderation = $moderator->analyze($newMessage);
            $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
            $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

            if ($toxicityScore > 0.85 || $insultScore > 0.85) {
                $error = 'Your comment update was rejected because it may be toxic or insulting.';
                $divVisibility = 'block';

                return $this->render('note/comment_update.html.twig', [
                    'error' => $error,
                    'divVisibility' => $divVisibility,
                    'comment' => $comment,
                    'note' => $note,
                    'mentionedUser' => $mentionedUser,
                    'noteId' => $note->getId(),
                ]);
            }

            $isMessageInvalid = $newMessage === $currentMessage;

            if ($isMessageInvalid) {
                $error = 'Error: Your comment cannot be the same as previously.';
                $divVisibility = 'block';
            } else {
                $comment->setMessage($newMessage);
                $comment->setIsEdited(true);
                $comment->setPublicationDate(new \DateTime());

                $em->persist($comment);
                $em->flush();

                return $this->redirectToRoute('app_note_show', ['noteId' => $note->getId()]);
            }
        }

        return $this->render('note/comment_update.html.twig', [
            'error' => $error,
            'divVisibility' => $divVisibility,
            'comment' => $comment,
            'note' => $note,
            'mentionedUser' => $mentionedUser,
            'noteId' => $note->getId(),
        ]);
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
            $commentReport->setStatus('pending');
            $commentReport->setCreationDate(new \DateTime());
            $commentReport->setReporterId($this->getUser()->getId());

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

        return $this->render('comment/report.html.twig', [
            'note' => $note,
            'comment' => $comment,
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

    #[Route('/note/{noteId}/comments', name: 'note_all_comments', methods: ['GET'])]
    public function getAllComments(int $noteId, NoteRepository $noteRepository): JsonResponse
    {
        $note = $noteRepository->find($noteId);
        if (!$note) {
            return $this->json(['error' => 'Note not found'], 404);
        }

        $comments = $note->getComments()->toArray();

        usort($comments, fn($a, $b) => $a->getPublicationDate() <=> $b->getPublicationDate());

        $commentsData = array_map(function ($comment) {
            return [
                'id' => $comment->getId(),
                'user' => [
                    'id' => $comment->getUser()->getId(),
                    'nametag' => $comment->getUser()->getNametag(),
                    'avatar' => $comment->getUser()->getAvatar(),
                ],
                'message' => $comment->getMessage(),
                'date' => $comment->getPublicationDate()->format('Y-m-d H:i'),
                'isEdited' => $comment->isEdited(),
                'humanTime' => $comment->getHumanTimeComment(),
                'upVote' => $comment->getUpVote(),
            ];
        }, $comments);

        return new JsonResponse(['comments' => $commentsData]);
    }

    #[Route('/note/{id}/toggle-pin', name: 'app_note_toggle_pin', methods: ['POST'])]
    public function togglePin(
        Request $request,
        Note $note,
        EntityManagerInterface $em
    ): JsonResponse {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_pin' . $note->getId(), $submittedToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        $user = $this->getUser();
        $ring = $note->getRing();

        if (!$ring) {
            return new JsonResponse(['error' => 'This note is not part of a community'], 400);
        }

        $ringMember = $em->getRepository(RingMembers::class)->findOneBy([
            'ring' => $ring,
            'user' => $user,
        ]);

        if (!$ringMember || $ringMember->getRole() !== 'owner') {
            return new JsonResponse(['error' => 'Only the community owner can pin/unpin notes'], 403);
        }

        if ($note->isPinned()) {
            $note->setIsPinned(false);
            $note->setPinnedAt(null);
        } else {
            $note->setIsPinned(true);
            $note->setPinnedAt(new \DateTime());
        }

        $em->flush();

        return new JsonResponse([
            'isPinned' => $note->isPinned(),
            'pinnedAt' => $note->getPinnedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/note/{id}/favorite', name: 'app_note_favorite', methods: ['POST'])]
    public function toggleFavorite(Note $note, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'User not logged in'], 403);
        }

        if ($user->hasFavorite($note)) {
            $user->removeFavorite($note);
            $status = 'removed';
        } else {
            $user->addFavorite($note);
            $status = 'added';
        }

        $em->flush();

        return $this->json(['status' => $status]);
    }
}
