<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\NewTicketForm;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class SupportController extends AbstractController
{
    #[Route('/new-ticket', name: 'app_new_ticket')]
    public function newTicket(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(NewTicketForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $content = $data['content'];

            $ticket = new Ticket();
            $ticket->setUser($user);
            $ticket->setType($type);
            $ticket->setContent($content);
            $ticket->setStatus('In progress...');
            $ticket->setCreationDate(new \DateTime());

            $em->persist($ticket);
            $em->flush();

            $this->addFlash('success', 'Your ticket has been sent!');
            return $this->redirectToRoute('app_my_tickets');
        }

        return $this->render('support/new_ticket.html.twig', [
            'newTicketForm' => $form->createView(),
        ]);
    }

    #[Route('/my-tickets', name: 'app_my_tickets')]
    public function showAllTickets(Request $request, TicketRepository $ticketRepository): Response
    {
        $user = $this->getUser();
        $myTickets = $ticketRepository->findBy(['user' => $user], ['creationDate' => 'DESC']);

        return $this->render('support/my_tickets.html.twig', [
           'tickets' => $myTickets
        ]);
    }

    #[Route('/ticket/{id}/preview', name: 'app_ticket_preview')]
    public function showPreviewTicket(Request $request, TicketRepository $ticketRepository, int $id): Response
    {
        $user = $this->getUser();

        $ticket = $ticketRepository->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        if ($ticket->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have access to this ticket.');
        }


        return $this->render('support/preview_ticket.html.twig', [
            'ticket' => $ticket,
        ]);
    }
}
