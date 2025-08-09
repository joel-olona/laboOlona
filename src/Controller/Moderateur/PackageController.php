<?php

namespace App\Controller\Moderateur;

use App\Entity\BusinessModel\Package;
use App\Form\BusinessModel\Package1Type;
use App\Repository\BusinessModel\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/moderateur/package')]
class PackageController extends AbstractController
{
    #[Route('/', name: 'app_moderateur_package_index', methods: ['GET'])]
    public function index(Request $request, PackageRepository $packageRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $type = $request->query->get('type', null);

        return $this->render('moderateur/package/index.html.twig', [
            'packages' => $packageRepository->paginatePackages($page, $type),
        ]);
    }

    #[Route('/new', name: 'app_moderateur_package_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $sluggerInterface): Response
    {
        $package = new Package();
        $package->setUpdatedAt(new \DateTime());
        $form = $this->createForm(Package1Type::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $package->setSlug($sluggerInterface->slug(strtolower($package->getName())));
            $entityManager->persist($package);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_package_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/package/new.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_package_show', methods: ['GET'])]
    public function show(Package $package): Response
    {
        return $this->render('moderateur/package/show.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_package_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Package $package, EntityManagerInterface $entityManager, SluggerInterface $sluggerInterface): Response
    {
        $form = $this->createForm(Package1Type::class, $package);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $package->setSlug($sluggerInterface->slug(strtolower($package->getName())));
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_package_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/package/edit.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_package_delete', methods: ['POST'])]
    public function delete(Request $request, Package $package, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$package->getId(), $request->request->get('_token'))) {
            $entityManager->remove($package);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_package_index', [], Response::HTTP_SEE_OTHER);
    }
}
