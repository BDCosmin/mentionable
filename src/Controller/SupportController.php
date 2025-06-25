<?php

namespace App\Controller;

use App\Form\SupportForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class SupportController extends AbstractController
{
    #[Route('/support', name: 'app_support')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(SupportForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data['email'])
                ->to('noreply@mentionable.com')
                ->subject('Mentionable - Support Message')
                ->text($data['description']);

            $mailer->send($email);

            $this->addFlash('success', 'Your message has been sent!');
            return $this->redirectToRoute('app_support');
        }

        return $this->render('support/index.html.twig', [
            'supportForm' => $form->createView(),
        ]);
    }
}
