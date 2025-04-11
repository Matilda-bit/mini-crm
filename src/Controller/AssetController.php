<?php

namespace App\Controller;


use App\Entity\Asset;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AssetController extends AbstractController
{
    #[Route('/asset', name: 'app_asset')]
    public function index(): Response
    {
        return $this->render('asset/index.html.twig', [
            'controller_name' => 'AssetController',
        ]);
    }


    #[Route('/api/assets', name: 'api_assets', methods: ['GET'])]
    public function getAllAssets(): JsonResponse
    {
        // Получаем все активы из базы данных
        $repository = $this->getDoctrine()->getRepository(Asset::class);
        $assets = $repository->findAll();

        if (!$assets) {
            return new JsonResponse(['error' => 'No assets found'], 404);
        }

        // Преобразуем активы в массив для отправки в ответе
        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'asset_name' => $asset->getAssetName(),
                'bid' => $asset->getBid(),
                'ask' => $asset->getAsk(),
                'lot_size' => $asset->getLotSize(),
                'date_update' => $asset->getDateUpdate()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data);
    }
}
