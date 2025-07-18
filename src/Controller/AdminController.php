<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\CommentReportRepository;
use App\Repository\NoteReportRepository;
use App\Repository\NoteRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ): Response
    {
        $users = $userRepository->findAll();
        $notes = $noteRepository->findAll();
        $rings = $ringRepository->findAll();
        $noteReports = $noteReportRepository->findAll();
        $commentReports = $commentReportRepository->findAll();

        $reportsNumber = count($noteReports) + count($commentReports);

        $typedNoteReports = array_map(function($r) {
            return ['type' => 'note', 'data' => $r];
        }, $noteReports);

        $typedCommentReports = array_map(function($r) {
            return ['type' => 'comment', 'data' => $r];
        }, $commentReports);

        $reports = array_merge($typedNoteReports, $typedCommentReports);

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
        ]);
    }

    #[Route('/admin/reports', name: 'admin_show_reports', methods: ['GET', 'POST'])]
    public function showReports(
        UserRepository $userRepository,
        NoteRepository $noteRepository,
        RingRepository $ringRepository,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
    ): Response
    {
        $noteReports = $noteReportRepository->findAll();
        $commentReports = $commentReportRepository->findAll();

        $reportsNumber = count($noteReports) + count($commentReports);

        $typedNoteReports = array_map(function($r) {
            return ['type' => 'note', 'data' => $r];
        }, $noteReports);

        $typedCommentReports = array_map(function($r) {
            return ['type' => 'comment', 'data' => $r];
        }, $commentReports);

        $reports = array_merge($typedNoteReports, $typedCommentReports);

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
        EntityManagerInterface $em,
        UserRepository $userRepository,
    ): Response
    {
        $report = $noteReportRepository->find($id);
        $note = null;
        $comment = null;

        if ($report) {
            $note = $report->getNote();
            $reporterId = $report->getReporterId() ?? 0;
        } else {
            $report = $commentReportRepository->find($id);
            if (!$report) {
                throw $this->createNotFoundException('Report not found.');
            }
            $comment = $report->getComment();
            $note = $comment?->getNote();
            $reporterId = $report->getReporterId() ?? 0;
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
        EntityManagerInterface $em,
    ): Response
    {
        $report = $noteReportRepository->find($id);

        $report->setStatus('done');
        $em->persist($report);
        $em->flush();

        $this->addFlash('success', "Report marked as done.");

        return $this->redirectToRoute('admin_show_reports');
    }

    #[Route('/admin/all-users', name: 'admin_show_users', methods: ['GET', 'POST'])]
    public function showUsers(
        UserRepository $userRepository,
        NoteRepository $noteRepository,
        RingRepository $ringRepository,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
    ): Response
    {
        $users = $userRepository->findBy([], ['creationDate' => 'DESC']);

        return $this->render('admin/all_users.html.twig', [
            'users' => $users,
        ]);
    }
}