<?php

namespace App\Controller;

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
        // Verifică dacă utilizatorul este autentificat
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Trebuie să fii logat pentru a crea o notă.');
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $nametag = $request->request->get('nametag'); // îl poți folosi cum dorești

            // Poți adăuga validări simple:
            if (empty(trim($content))) {
                $this->addFlash('error', 'Conținutul notei nu poate fi gol!');
                return $this->redirectToRoute('app_note_new');
            }

            $userMentioned = $em->getRepository(User::class)->findOneBy(['nametag' => $nametag]);
            if (!$userMentioned) {
                $this->addFlash('error', 'Utilizatorul mentionat nu există!');
                return $this->redirectToRoute('app_note_new');
            }

            $note = new Note();
            $note->setContent($content);
            $note->setUser($user);
            $nametag = $request->request->get('nametag');
            $note->setNametag($nametag);
            // poți salva sau folosi $nametag cum dorești

            $em->persist($note);
            $em->flush();

            $this->addFlash('success', 'Notă creată cu succes!');

            // Redirecționează unde vrei, ex. pagina notei create
            return $this->redirectToRoute('app_note_new');
        }

        $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['id' => 'DESC']);

        // Dacă este GET, afișează formularul
        return $this->render('default/index.html.twig', [
            'notes' => $notes,
        ]);
    }
}
