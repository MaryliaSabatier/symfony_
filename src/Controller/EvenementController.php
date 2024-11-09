<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvenementController extends AbstractController
{
    #[Route('/admin/evenements', name: 'admin_event_management')]
    public function list(EvenementRepository $evenementRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Accès limité aux admins
        $evenements = $evenementRepository->findAll();

        return $this->render('admin/manage_events.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/admin/evenements/create', name: 'admin_create_event')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Accès limité aux admins
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenement->setAuteur($this->getUser());
            $evenement->setDateCreation(new \DateTime());

            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('admin_event_management');
        }

        return $this->render('admin/create_event.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/evenements/edit/{id}', name: 'admin_edit_event')]
    public function edit(Evenement $evenement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Accès limité aux admins
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');

            return $this->redirectToRoute('admin_event_management');
        }

        return $this->render('admin/edit_event.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/admin/evenements/delete/{id}', name: 'admin_delete_event', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Accès limité aux admins
        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_event_management');
    }
}
