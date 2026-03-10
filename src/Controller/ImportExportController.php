<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ImportExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('/settings/data')]
class ImportExportController extends AbstractController
{
    public function __construct(
        private ImportExportService $importExportService
    ) {}

    #[Route('/export', name: 'import_export_export', methods: ['GET'])]
    public function export(): Response
    {
        return $this->render('import_export/export.html.twig');
    }

    #[Route('/export/download', name: 'import_export_download', methods: ['POST'])]
    public function doExport(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $format = $request->request->get('format', 'json');

        $data = $this->importExportService->exportAll($user);

        $filename = 'prompt-wallet-export-' . date('Y-m-d-His');

        if ($format === 'yaml') {
            $content = Yaml::dump($data, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            $filename .= '.yaml';
            $contentType = 'application/x-yaml';
        } else {
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filename .= '.json';
            $contentType = 'application/json';
        }

        $response = new Response($content);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/import', name: 'import_export_import', methods: ['GET'])]
    public function import(): Response
    {
        return $this->render('import_export/import.html.twig');
    }

    #[Route('/import', name: 'import_export_do_import', methods: ['POST'])]
    public function doImport(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = $request->files->get('import_file');
        $strategy = $request->request->get('strategy', 'skip');

        if (!$file || !$file->isValid()) {
            $this->addFlash('danger', 'Please upload a valid file.');
            return $this->redirectToRoute('import_export_import');
        }

        $content = file_get_contents($file->getPathname());
        $ext = strtolower($file->getClientOriginalExtension());

        try {
            if ($ext === 'yaml' || $ext === 'yml') {
                $data = Yaml::parse($content);
            } else {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Failed to parse file: ' . $e->getMessage());
            return $this->redirectToRoute('import_export_import');
        }

        if (!is_array($data)) {
            $this->addFlash('danger', 'Invalid file format.');
            return $this->redirectToRoute('import_export_import');
        }

        $result = $this->importExportService->importAll($user, $data, $strategy);

        $this->addFlash('success', sprintf(
            'Import complete: %d created, %d updated, %d skipped.',
            $result['created'],
            $result['updated'],
            $result['skipped']
        ));

        return $this->redirectToRoute('import_export_import');
    }
}
