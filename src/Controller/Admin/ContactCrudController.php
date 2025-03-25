<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;

class ContactCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $replyAction = Action::new('reply', 'Répondre')
            ->linkToRoute('admin_contact_reply', function (Contact $contact) {
                return ['id' => $contact->getId()];
            })
            ->setCssClass('btn btn-success');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT) // Supprime "Edit"
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $replyAction); // Ajoute "Répondre"
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('subject', 'Objet'),
            EmailField::new('email', 'Email'),
            TextareaField::new('content', 'Message')->hideOnIndex(),
            TextareaField::new('response', 'Réponse')->hideOnIndex(), // 🔥 Affiche la réponse
            DateTimeField::new('created_at', 'Date de création')->setDisabled(),
        ];
    }


    #[Route('/admin/contact/reply/{id}', name: 'admin_contact_reply')]
    public function reply(Contact $contact, Request $request, MailerInterface $mailer, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $responseContent = $request->request->get('response');

            // 🔥 Sauvegarde de la réponse en base de données
            $contact->setResponse($responseContent);
            $entityManager->persist($contact);
            $entityManager->flush();

            // 🔥 Envoi de l'email
            $email = (new Email())
                ->from('votre-email@domaine.com') // Remplace par ton email
                ->to($contact->getEmail())
                ->subject('Réponse à votre message : ' . $contact->getSubject())
                ->text($responseContent);

            $mailer->send($email);

            $this->addFlash('success', 'Réponse enregistrée et envoyée avec succès !');

            $url = $adminUrlGenerator
                ->setController(ContactCrudController::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl();

            return new RedirectResponse($url);
        }

        return $this->render('admin/contact_reply.html.twig', [
            'contact' => $contact
        ]);
    }
}
