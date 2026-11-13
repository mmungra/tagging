<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Form\AssetType;
use App\Repository\AssetRepository;
use App\Service\AssetCsvImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class AssetImportController extends AbstractController
{
    private LoggerInterface $logger;

    #[Route('/asset/import', name: 'asset_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        AssetCsvImportService $assetCsvImportService
    ): Response {


        if ($request->isMethod('POST')) {
            // Validate CSRF token.
            if (!$this->isCsrfTokenValid(
                'asset_csv_import',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Invalid CSRF token.'
                );
            }

            $file = $request->files->get('csv_file');

            if (!$file instanceof UploadedFile || !$file->isValid()) {
                $this->addFlash('error', 'Please select a valid CSV file.');

                return $this->redirectToRoute('asset_import');
            }

            // Validate the extension.
            if (strtolower($file->getClientOriginalExtension()) !== 'csv') {
                $this->addFlash('error', 'Only CSV files are allowed.');

                return $this->redirectToRoute('asset_import');
            }

            // Limit the upload size to 5 MB.
            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash(
                    'error',
                    'The CSV file must not exceed 5 MB.'
                );

                return $this->redirectToRoute('asset_import');
            }

            $importDirectory = $this->getParameter('kernel.project_dir')
                . '/var/import';

            if (!is_dir($importDirectory)) {
                mkdir($importDirectory, 0775, true);
            }

            // Generate a safe, unique filename.
            $filename = 'assets_' . bin2hex(random_bytes(8)) . '.csv';

            try {
                $file->move($importDirectory, $filename);

                $result = $assetCsvImportService->import($filename);
                if (count($result['errors']) == 0) {
                    $this->addFlash(
                        'success',
                        sprintf(
                            '%d assets imported successfully.',
                            $result['imported']
                        )
                    );
                    return $this->redirectToRoute('index');
                } else {
                    $this->addFlash(
                        'error',
                        'Import failed validation. No assets were imported.'
                    );
                    return $this->redirectToRoute('index');
                }
            } catch (\Throwable $exception) {
                // Log technical details; do not expose them to the user.
                $this->logger->error('Asset CSV import failed.', [
                    'exception' => $exception,
                ]);

                $this->addFlash(
                    'error',
                    'The import failed due to a server or database error.'
                );
            } finally {
                // Remove the uploaded CSV after processing.
                $uploadedPath = $importDirectory . '/' . $filename;

                if (is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }
            }
        }

        return $this->render('assets/import.html.twig', [

        ]);
    }

    public function __construct( LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    #[Route(
        '/asset/{id}',
        name: 'asset_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        Asset $asset
    ): Response {
        return $this->render('assets/show.html.twig', [
            'asset' => $asset,
        ]);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        AssetRepository $assetRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $totalAssets = $assetRepository->countAll();
        $totalPages = max(1, (int) ceil($totalAssets / $limit));

        // Clamp the requested page to the available range.
        $page = min($page, $totalPages);

        $assets = $assetRepository->findPaginated($page, $limit);

        return $this->render('assets/list.html.twig', [
            'assets' => $assets,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalAssets' => $totalAssets,
            'limit' => $limit,
        ]);
    }


    #[Route('/asset/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $asset = new Asset();

        $form = $this->createForm(AssetType::class, $asset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($asset);
            $entityManager->flush();

            $this->addFlash('success', 'Asset created successfully.');

            return $this->redirectToRoute('index');
        }

        return $this->render('assets/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/asset/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Asset $asset,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_asset_' . $asset->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($asset);
        $entityManager->flush();

        $this->addFlash('success', 'Asset deleted successfully.');

        return $this->redirectToRoute('index');
    }

}
