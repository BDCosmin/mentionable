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

        return $this->render('admin/reports.html.twig', [
            'reportsNumber' => $reportsNumber,
            'reports' => $reports,
        ]);
    }

    #[Route('/admin/report/{id}/done', name: 'admin_action_done_reports', methods: ['GET', 'POST'])]
    public function actionReports(
        int $id,
        NoteReportRepository $noteReportRepository,
        CommentReportRepository $commentReportRepository,
        EntityManagerInterface $em,
    ): Response
    {
        $report = $noteReportRepository->find($id);
        $type = 'note';
        $note = null;
        $comment = null;

        if ($report) {
            $note = $report->getNote();
        } else {
            $report = $commentReportRepository->find($id);
            if (!$report) {
                throw $this->createNotFoundException('Report not found.');
            }
            $type = 'comment';
            $comment = $report->getComment();
            $note = $comment?->getNote();
        }

        $notification = new Notification();
        $notification->setType('closed_report');
        $notification->setSender($this->getUser());
        $notification->setReceiver($report->getUser());
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

        return $this->render('admin/reports.html.twig', [
            'report' => $report,
        ]);
    }
}