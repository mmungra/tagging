<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Repository\AssetRepository;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssetQrCodeController extends AbstractController
{
    #[Route(
        '/asset/{id}/qr-code',
        name: 'asset_qr_code',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function generate(
        int $id,
        AssetRepository $assetRepository,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $asset = $assetRepository->find($id);

        if (!$asset instanceof Asset) {
            throw $this->createNotFoundException('PC asset not found.');
        }

        // Encode the URL of the asset detail page.
        $assetUrl = $urlGenerator->generate(
            'asset_show',
            ['id' => $asset->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $qrCode = new QrCode(
            data: $assetUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response(
            $result->getString(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => sprintf(
                    'inline; filename="qr-%s.png"',
                    $asset->getAssetId()
                ),
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
