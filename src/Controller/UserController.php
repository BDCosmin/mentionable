<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(NoteRepository $noteRepository): Response
    {
        $user = $this->getUser();
        $notes = $noteRepository->findBy(['user' => $user]);

        $adsCount = count($notes);

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'adsCount' => $adsCount,
            'notes' => $notes,
        ]);
    }

//    #[Route('/user/advertisments', name: 'user_advertisments')]
//    public function advertisments(Request $request, AdvertismentRepository $advertismentRepository,PaginatorInterface $paginator): Response
//    {
//        $user = $this->getUser();
//
//        $query = $advertismentRepository->pagination($user);
//
//        $pagination = $paginator->paginate(
//            $query,
//            $request->query->getInt('page', 1),
//            4
//        );
//
//
//        return $this->render('user/advertisments.html.twig', [
//            'user' => $user,
//            'pagination' => $pagination,
//            'posted_ads' => $pagination->getTotalItemCount(),
//        ]);
//    }
}
