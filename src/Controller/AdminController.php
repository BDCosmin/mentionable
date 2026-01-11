<?php

namespace App\Controller;

use App\Entity\AdminTask;
use App\Entity\Interest;
use App\Entity\Notification;
use App\Entity\Ring;
use App\Entity\User;
use App\Repository\AdminTaskRepository;
use App\Repository\CommentReplyReportRepository;
use App\Repository\CommentReportRepository;
use App\Repository\InterestRepository;
use App\Repository\NoteReportRepository;
use App\Repository\NoteRepository;
use App\Repository\RingRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET', 'POST'])]
    public function index(
        UserRepository $userRepository,
        NoteRepository $noteRepository,
        RingRepository $ringRepository,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
        InterestRepository $interestRepository,
        TicketRepository $ticketRepository,
        CommentReplyReportRepository $commentReplyReportRepository,
    ): Response
    {
        $users = $userRepository->findAll();
        $notes = $noteRepository->findAll();
        $rings = $ringRepository->findAll();
        $noteReports = $noteReportRepository->findAll();
        $commentReports = $commentReportRepository->findAll();
        $interests = $interestRepository->findAll();
        $tickets = $ticketRepository->findAll();
        $replyReports = $commentReplyReportRepository->findAll();

        $reportsNumber = count($noteReports) + count($commentReports) + count($replyReports);

        $typedNoteReports = array_map(function($r) {
            return ['type' => 'note', 'data' => $r];
        }, $noteReports);

        $typedCommentReports = array_map(function($r) {
            return ['type' => 'comment', 'data' => $r];
        }, $commentReports);

        $typedReplyReports = array_map(function($r) {
            return ['type' => 'reply', 'data' => $r];
        }, $replyReports);

        $reports = array_merge($typedNoteReports, $typedCommentReports, $typedReplyReports);

        usort($reports, function ($a, $b) {
            return $b['data']->getCreationDate() <=> $a['data']->getCreationDate();
        });

        $reports = array_slice($reports, 0, 5);


        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'notes' => $notes,
            'rings' => $rings,
            'reportsNumber' => $reportsNumber,
            'reports' => $reports,
            'interests' => $interests,
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/manage-tasks', name: 'admin_manage_tasks', methods: ['GET', 'POST'])]
    public function adminManageTasks(
        AdminTaskRepository $adminTaskRepository,
        UserRepository $userRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $devs = $userRepository->findAdmins();

        if ($request->isMethod('POST')) {
            $newTask = new AdminTask();
            $newTask->setTitle($request->request->get('title'));
            $newTask->setDescription($request->request->get('description'));
            $newTask->setPriority($request->request->get('priority'));

            $assignedUserId = $request->request->get('assignedTo');
            if ($assignedUserId) {
                $assignedUser = $userRepository->find($assignedUserId);
                $newTask->setAssignedTo($assignedUser);
            }

            $em->persist($newTask);
            $em->flush();
        }

        $tasks = $adminTaskRepository->findAll();

        $assignedTasks = [];
        foreach ($tasks as $taskItem) {
            $user = $taskItem->getAssignedTo();
            if ($user) {
                $username = $user->getNametag();
                if (!isset($assignedTasks[$username])) {
                    $assignedTasks[$username] = [];
                }
                $assignedTasks[$username][] = $taskItem;
            }
        }

        return $this->render('admin/all_tasks.html.twig', [
            'tasks' => $tasks,
            'tasksNumber' => count($tasks),
            'assignedTasks' => $assignedTasks,
            'devs' => $devs
        ]);
    }

    #[Route('/admin/edit-task/{id}', name: 'admin_edit_task', methods: ['POST'])]
    public function editTask(
        AdminTask $task,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {

        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setPriority($request->request->get('priority'));

        $assignedId = $request->request->get('assignedTo');
        if ($assignedId) {
            $assignedUser = $userRepository->find($assignedId);
            $task->setAssignedTo($assignedUser);
        } else {
            $task->setAssignedTo(null);
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/admin/delete-task/{id}', name: 'admin_delete_task', methods: ['POST'])]
    public function deleteTask(AdminTask $task, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_task', $submittedToken)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $em->remove($task);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/admin/reports', name: 'admin_show_reports', methods: ['GET', 'POST'])]
    public function showReports(
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
        CommentReplyReportRepository $commentReplyReportRepository,
    ): Response
    {
        $noteReports = $noteReportRepository->findAll();
        $commentReports = $commentReportRepository->findAll();
        $replyReports = $commentReplyReportRepository->findAll();

        $reportsNumber = count($noteReports) + count($commentReports) + count($replyReports);

        $typedNoteReports = array_map(function($r) {
            return ['type' => 'note', 'data' => $r];
        }, $noteReports);

        $typedCommentReports = array_map(function($r) {
            return ['type' => 'comment', 'data' => $r];
        }, $commentReports);

        $typedReplyReports = array_map(function($r) {
            return ['type' => 'reply', 'data' => $r];
        }, $replyReports);

        $reports = array_merge($typedNoteReports, $typedCommentReports, $typedReplyReports);

        usort($reports, function ($a, $b) {
            return $b['data']->getCreationDate() <=> $a['data']->getCreationDate();
        });

        return $this->render('admin/reports.html.twig', [
            'reportsNumber' => $reportsNumber,
            'reports' => $reports,
        ]);
    }

    #[Route('/admin/report/{id}/done', name: 'admin_action_done_reports', methods: ['GET', 'POST'])]
    public function actionDoneReports(
        int $id,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
        CommentReplyReportRepository $commentReplyReportRepository,
        EntityManagerInterface $em,
        UserRepository $userRepository,
    ): Response
    {
        $report = $noteReportRepository->find($id);
        $comment = null;

        if ($report) {
            $note = $report->getNote();
            $reporterId = $report->getReporterId() ?? 0;
        } else {
            $report = $commentReportRepository->find($id);
            if ($report) {
                $comment = $report->getComment();
                $note = $comment?->getNote();
                $reporterId = $report->getReporterId() ?? 0;
            } else {
                $report = $commentReplyReportRepository->find($id);
                if (!$report) {
                    throw $this->createNotFoundException('Report not found.');
                }
                $comment = $report->getReply()?->getComment();
                $note = $comment?->getNote();
                $reporterId = $report->getReporterId() ?? 0;
            }
        }

        $receiver = null;
        if ($reporterId > 0) {
            $receiver = $userRepository->find($reporterId);
        }
        if (!$receiver) {
            $receiver = $this->getUser();
        }

        $notification = new Notification();
        $notification->setType('closed_report');
        $notification->setSender($this->getUser());
        $notification->setReceiver($receiver);
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);

        if ($note) {
            $notification->setNote($note);
        }
        if ($comment) {
            $notification->setComment($comment);
        }

        $report->setStatus('done');
        $em->persist($notification);
        $em->flush();

        $this->addFlash('success', "Report marked as done.");

        return $this->redirectToRoute('admin_show_reports');
    }

    #[Route('/admin/report/{id}/none', name: 'admin_action_none_reports', methods: ['GET', 'POST'])]
    public function actionNoneReports(
        int $id,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
        CommentReplyReportRepository $commentReplyReportRepository,
        EntityManagerInterface $em,
    ): Response
    {
        $report = $noteReportRepository->find($id)
            ?? $commentReportRepository->find($id)
            ?? $commentReplyReportRepository->find($id);

        if (!$report) {
            $this->addFlash('warning', 'Report not found.');
            return $this->redirectToRoute('admin_show_reports');
        }

        $report->setStatus('done');
        $em->persist($report);
        $em->flush();

        $this->addFlash('success', "Report marked as done.");

        return $this->redirectToRoute('admin_show_reports');
    }

    #[Route('/admin/all-users', name: 'admin_show_users', methods: ['GET', 'POST'])]
    public function showUsers(
        UserRepository $userRepository,
    ): Response
    {
        $users = $userRepository->findBy([], ['creationDate' => 'DESC']);

        return $this->render('admin/all_users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}/kick', name: 'admin_user_kick', methods: ['POST'])]
    public function kickUser(EntityManagerInterface $em, Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('kick-user-' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'The user has been kicked.');
        return $this->redirectToRoute('admin_show_users');
    }

    #[Route('/admin/user/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
    public function banUser(EntityManagerInterface $em, Request $request, User $user, RingRepository $ringRepository): Response
    {
        if (!$this->isCsrfTokenValid('ban-user-' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_users');
        }

        $user->setIsBanned(true);
        foreach ($user->getRings()->toArray() as $ring) {
            $ring->setIsSuspended(1);
            $ring->setSuspensionReason('This ring has been suspended automatically due to actions associated with its owner.');
            $em->persist($ring);
        }
        $em->flush();

        $this->addFlash('success', 'The user has been banned.');
        return $this->redirectToRoute('admin_show_users');
    }

    #[Route('/admin/user/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
    public function unbanUser(EntityManagerInterface $em, Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('unban-user-' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_users');
        }

        $user->setIsBanned(false);
        foreach ($user->getRings()->toArray() as $ring) {
            $ring->setIsSuspended(0);
            $ring->setSuspensionReason(NULL);
            $em->persist($ring);
        }
        $em->flush();

        $this->addFlash('success', 'The user has been unbanned.');
        return $this->redirectToRoute('admin_show_users');
    }

    #[Route('/admin/all-rings', name: 'admin_show_rings', methods: ['GET', 'POST'])]
    public function showRings(
        RingRepository $ringRepository,
    ): Response
    {
        $rings = $ringRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/all_rings.html.twig', [
            'rings' => $rings,
        ]);
    }

    #[Route('/admin/ring/{id}/delete', name: 'admin_ring_delete', methods: ['POST'])]
    public function deleteRingByAdmin(EntityManagerInterface $em, Request $request, Ring $ring): Response
    {
        if (!$this->isCsrfTokenValid('delete-ring-' . $ring->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_rings');
        }

        $em->remove($ring);
        $em->flush();

        $this->addFlash('success', 'The ring has been deleted.');
        return $this->redirectToRoute('admin_show_rings');
    }

    #[Route('/admin/ring/{id}/suspend', name: 'admin_suspend_ring', methods: ['POST'])]
    public function suspendRing(EntityManagerInterface $em, Request $request, Ring $ring): Response
    {
        if (!$this->isCsrfTokenValid('suspend-ring-' . $ring->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_rings');
        }

        $suspensionReason = $request->request->get('suspensionReason');

        $ring->setIsSuspended(true);
        $ring->setSuspensionReason($suspensionReason);
        $em->flush();

        $this->addFlash('success', 'The ring has been suspended.');
        return $this->redirectToRoute('admin_show_rings');
    }

    #[Route('/admin/ring/{id}/reactivate', name: 'admin_reactivate_ring', methods: ['POST'])]
    public function reactivateRing(EntityManagerInterface $em, Request $request, Ring $ring): Response
    {
        if (!$this->isCsrfTokenValid('reactivate-ring-' . $ring->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_show_rings');
        }

        $ring->setIsSuspended(false);
        $ring->setSuspensionReason(NULL);
        $em->flush();

        $this->addFlash('success', 'The ring has been reactivated.');
        return $this->redirectToRoute('admin_show_rings');
    }

    #[Route('/admin/all-interests', name: 'admin_show_interests', methods: ['GET', 'POST'])]
    public function showInterests(
        InterestRepository $interestRepository,
    ): Response
    {
        $interests = $interestRepository->findAll();

        return $this->render('admin/all_interests.html.twig', [
            'interests' => $interests,
        ]);
    }

    #[Route('/admin/interest/{id}/delete', name: 'admin_interest_delete', methods: ['POST'])]
    public function deleteInterestByAdmin(EntityManagerInterface $em, Request $request, Interest $interest): Response
    {
        if (!$this->isCsrfTokenValid('delete-interest-' . $interest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalid.');
            return $this->redirectToRoute('admin_show_interests');
        }

        $rings = $interest->getRings();
        $defaultInterestByUser = [];

        foreach ($rings as $ring) {
            $ring->setIsSuspended(true);

            $user = $ring->getUser();

            if (!isset($defaultInterestByUser[$user->getId()])) {
                $existingDefault = $em->getRepository(Interest::class)->findOneBy([
                    'user' => $user,
                    'title' => 'default'
                ]);

                if ($existingDefault) {
                    $defaultInterestByUser[$user->getId()] = $existingDefault;
                } else {
                    $defaultInterest = new Interest();
                    $defaultInterest->setUser($user);
                    $defaultInterest->setTitle('default');
                    $em->persist($defaultInterest);
                    $defaultInterestByUser[$user->getId()] = $defaultInterest;
                }
            }

            $ring->setInterest($defaultInterestByUser[$user->getId()]);
            $em->persist($ring);
        }

        $em->remove($interest);
        $em->flush();

        $this->addFlash('success', 'The interest has been deleted.');
        return $this->redirectToRoute('admin_show_interests');
    }

    #[Route('/admin/manage-tickets', name: 'admin_show_tickets', methods: ['GET', 'POST'])]
    public function showTickets(
        UserRepository $userRepository,
        TicketRepository $ticketRepository,
    ): Response
    {
        $tickets = $ticketRepository->findBy([], ['creationDate' => 'DESC']);

        return $this->render('admin/all_tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/ticket/{id}/reply', name: 'admin_ticket_reply', methods: ['POST','GET'])]
    public function replyToTicket(
        int $id,
        Request $request,
        TicketRepository $ticketRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $ticket = $ticketRepository->find($id);

        if (!$ticket) {
            return new JsonResponse(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $replyContent = $request->request->get('message');

        if (!$replyContent) {
            return new JsonResponse(['success' => false, 'message' => 'Message cannot be empty.'], 400);
        }

        try {
            $ticket->setAdminReply($replyContent);

            $em->persist($ticket);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'reply' => $replyContent,
                'status' => $ticket->getStatus(),
                'ticketId' => $ticket->getId(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Server error occurred.'], 500);
        }
    }

    #[Route('/admin/ticket/{id}/mark-as-solved', name: 'admin_ticket_solved', methods: ['POST','GET'])]
    public function markAsSolvedTicket(
        int $id,
        TicketRepository $ticketRepository,
        EntityManagerInterface $em
    ): Response {
        $ticket = $ticketRepository->find($id);

        if (!$ticket) {
            return new JsonResponse(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $ticket->setStatus('Resolved');

        $notification = new Notification();
        $notification->setType('admin_ticket_solved');
        $notification->setReceiver($ticket->getUser());
        $notification->setSender($this->getUser());
        $notification->setTicket($ticket);
        $notification->setIsRead(false);
        $notification->setNotifiedDate(new \DateTime());

        $em->persist($ticket);
        $em->persist($notification);
        $em->flush();

        return $this->redirectToRoute('app_ticket_preview', ['id' => $ticket->getId()]);

    }
}