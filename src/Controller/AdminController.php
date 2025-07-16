<?php

namespace App\Controller;

use App\Repository\CommentReportRepository;
use App\Repository\NoteReportRepository;
use App\Repository\NoteRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
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
}