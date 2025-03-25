<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();  // Crée une nouvelle instance de l'entité Contact
        $form = $this->createForm(ContactType::class, $contact);  // Lie le formulaire à l'entité

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir la date de création
            $contact->setCreatedAt(new \DateTimeImmutable());

            // Persist l'entité Contact dans la base de données
            $entityManager->persist($contact);
            $entityManager->flush();

            // Message flash et redirection
            $this->addFlash('success', 'Votre message a bien été envoyé !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
