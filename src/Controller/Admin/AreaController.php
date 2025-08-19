<?php

namespace App\Controller\Admin;

use App\Entity\Area;
use App\Form\AreaType;
use App\Repository\AreaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/areas')]
class AreaController extends AbstractController
{
    #[Route('/', name: 'admin_area_index', methods: ['GET'])]
    public function index(Request $request, AreaRepository $areaRepository): Response
    {
        $search = $request->query->get('search');
        $estado = $request->query->get('estado');
        
        // Convert string 'true'/'false' to boolean or null if not set
        $activo = $estado === 'true' ? true : ($estado === 'false' ? false : null);
        
        $areas = $search || $estado !== null 
            ? $areaRepository->search($search, $activo)
            : $areaRepository->findBy([], ['nombre' => 'ASC']);

        // If it's an AJAX request, return only the table content
        if ($request->isXmlHttpRequest()) {
            $content = $this->renderView('admin/area/_table.html.twig', [
                'areas' => $areas,
                'search' => $search,
                'estado' => $estado,
            ]);
            return new Response($content);
        }

        return $this->render('admin/area/index.html.twig', [
            'areas' => $areas,
            'search' => $search,
            'estado' => $estado,
        ]);
    }

    #[Route('/nuevo', name: 'admin_area_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $area = new Area();
        $form = $this->createForm(AreaType::class, $area);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($area);
            $entityManager->flush();

            $this->addFlash('success', 'El área se ha creado correctamente.');
            return $this->redirectToRoute('admin_area_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/area/new.html.twig', [
            'area' => $area,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_area_show', methods: ['GET'])]
    public function show(Area $area): Response
    {
        return $this->render('admin/area/show.html.twig', [
            'area' => $area,
        ]);
    }

    #[Route('/{id}/editar', name: 'admin_area_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Area $area, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AreaType::class, $area);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'El área se ha actualizado correctamente.');
            return $this->redirectToRoute('admin_area_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/area/edit.html.twig', [
            'area' => $area,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_area_delete', methods: ['POST'])]
    public function delete(Request $request, Area $area, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$area->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($area);
                $entityManager->flush();
                $this->addFlash('success', 'El área se ha eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo eliminar el área porque tiene tickets asociados.');
            }
        }

        return $this->redirectToRoute('admin_area_index', [], Response::HTTP_SEE_OTHER);
    }
}
