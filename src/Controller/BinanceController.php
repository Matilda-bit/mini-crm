<?php 

namespace App\Controller;

use App\Repository\AssetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BinanceController extends AbstractController
{
    private $assetRepository;

    public function __construct(AssetRepository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    #[Route('/binance', name: 'app_binance')]
    public function index(): Response
    {
        return $this->render('binance/index.html.twig', [
            'controller_name' => 'BinanceController',
        ]);
    }

    #[Route('/api/binance', name: 'binance_data', methods: ['GET'])]
    public function getBinanceData(): JsonResponse
    {
        $asset = $this->assetRepository->findOneBy(['asset_name' => 'BTC/USD']);

        if (!$asset) {
            return new JsonResponse(['error' => 'Asset not found'], 404);
        }

        return new JsonResponse([
            'controller_name' => 'BinanceController',
            'bid' => $asset->getBid(),
            'ask' => $asset->getAsk(),
            'lot_size' => $asset->getLotSize(),
            'date_update' => $asset->getDateUpdate(),
        ]);
    }

}
