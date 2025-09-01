<?php

namespace App\Controller\Maintenance;

use App\Entity\Machine;
use App\Entity\Office;
use App\Form\MachineType;
use App\Form\OfficeType;
use App\Repository\OfficeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/offices', name: 'office_')]
class OfficeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(OfficeRepository $officeRepository): Response
    {
        $offices = $officeRepository->findAll();
        
        return $this->render('maintenance/office/index.html.twig', [
            'offices' => $offices,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_AUDITOR') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'No tiene permisos para crear oficinas.');
            return $this->redirectToRoute('office_office_index');
        }

        $office = new Office();
        $form = $this->createForm(OfficeType::class, $office);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($office);
            $entityManager->flush();
            
            $this->addFlash('success', 'Oficina creada correctamente.');
            return $this->redirectToRoute('office_office_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance/office/new.html.twig', [
            'office' => $office,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(Request $request, Office $office, EntityManagerInterface $entityManager): Response
    {
        $machine = new Machine();
        $machine->setOffice($office);
        
        $form = $this->createForm(MachineType::class, $machine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($machine);
            $entityManager->flush();
            
            $this->addFlash('success', 'Máquina agregada correctamente.');
            return $this->redirectToRoute('office_office_show', ['id' => $office->getId()]);
        }

        // Create edit forms for each machine
        $editForms = [];
        foreach ($office->getMachines() as $existingMachine) {
            $editForms[$existingMachine->getId()] = $this->createForm(MachineType::class, $existingMachine, [
                'action' => $this->generateUrl('office_office_machine_edit', ['id' => $existingMachine->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('maintenance/office/show.html.twig', [
            'office' => $office,
            'form' => $form->createView(),
            'edit_forms' => $editForms,
        ]);
    }

    #[Route('/machine/{id}/edit', name: 'machine_edit', methods: ['POST'])]
    public function editMachine(Request $request, Machine $machine, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_AUDITOR') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'No tiene permisos para editar máquinas.');
            return $this->redirectToRoute('office_office_show', ['id' => $machine->getOffice()->getId()]);
        }

        $form = $this->createForm(MachineType::class, $machine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Máquina actualizada correctamente.');
        } else if ($form->isSubmitted()) {
            $this->addFlash('error', 'Error al actualizar la máquina. Verifique los datos ingresados.');
        }

        return $this->redirectToRoute('office_office_show', ['id' => $machine->getOffice()->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Office $office, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_AUDITOR') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'No tiene permisos para editar oficinas.');
            return $this->redirectToRoute('office_office_show', ['id' => $office->getId()]);
        }

        $form = $this->createForm(OfficeType::class, $office);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Oficina actualizada correctamente.');
            return $this->redirectToRoute('office_office_show', ['id' => $office->getId()]);
        }

        return $this->render('maintenance/office/edit.html.twig', [
            'office' => $office,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Office $office, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Solo los administradores pueden eliminar oficinas.');
            return $this->redirectToRoute('office_office_index');
        }

        if ($this->isCsrfTokenValid('delete'.$office->getId(), $request->request->get('_token'))) {
            $entityManager->remove($office);
            $entityManager->flush();
            $this->addFlash('success', 'Oficina eliminada correctamente.');
        } else {
            $this->addFlash('error', 'Token de seguridad inválido.');
        }

        return $this->redirectToRoute('office_index');
    }

    #[Route('/machine/{id}/delete', name: 'machine_delete', methods: ['POST'])]
    public function deleteMachine(Request $request, Machine $machine, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_AUDITOR') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'No tiene permisos para eliminar máquinas.');
            return $this->redirectToRoute('office_office_index');
        }

        $officeId = $machine->getOffice()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$machine->getId(), $request->request->get('_token'))) {
            $entityManager->remove($machine);
            $entityManager->flush();
            $this->addFlash('success', 'Máquina eliminada correctamente.');
        } else {
            $this->addFlash('error', 'Token de seguridad inválido.');
        }

        return $this->redirectToRoute('office_office_show', ['id' => $officeId]);
    }
}
