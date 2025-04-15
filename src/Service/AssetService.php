<?php

namespace App\Service;
use App\Entity\Asset;
use App\Entity\Trade;
use Doctrine\ORM\EntityManagerInterface;

class AssetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function getAssetByName(string $assetName): ?Asset
    {
        return $this->em->getRepository(Asset::class)->findOneBy(['assetName' => $assetName]);
    }

    public function getCurrentRate(Asset $asset, string $position): float
    {
        //Trade::BUY
        return $position === Trade::BUY ? $asset->getAsk() : $asset->getBid();
    }

    public function shouldConvert(Asset $asset, string $userCurrency): bool
    {
        return $asset->getAssetName() === 'BTC/USD' && $userCurrency === 'EUR';
    }

    public function getConversionRate(Asset $asset, string $userCurrency): float
    {
        return $this->shouldConvert($asset, $userCurrency) ? 0.9215 : 1.0;
    }
}
