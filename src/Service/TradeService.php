<?php
//Описывает бизнес-логику — "что происходит"
//Это бизнес-логика приложения.
//Решает: "что делать, если пользователь открыл сделку?" или "как закрыть сделку?"
namespace App\Service;

use App\Entity\User;
use App\Entity\Trade;
use App\Entity\Asset;

use App\Repository\TradeRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;

class TradeService
{

    public function __construct(
        private SessionInterface $session,
        private AssetService $assetService,
        private TradeRepository $tradeRepository,
        private EntityManagerInterface $em,
    ) {}
    
    public function handleTrade( Request $request, UserInterface $user)
    {

        $referer = $request->headers->get('referer');
        $targetUserId = $request->request->get('target_user');
        $position = $request->request->get('position');
        $lotCount = (float) $request->request->get('lot_count');
        $sl = $request->request->get('sl');
        $tp = $request->request->get('tp');
        $assetName = $request->request->get('asset');
        $errorTitle = 'open_trade_error';
        $successTitle = 'open_trade_success';

        $targetUser = $this->em->getRepository(User::class)->find($targetUserId); 
        $asset = $this->assetService->getAssetByName($assetName);

        if (!$this->validateTradeRequest($targetUser, $asset, $errorTitle)) {
            return false;
        }
        $trade = $this->createTrade($targetUser, $user, $position, $lotCount, $sl ?: null, $tp ?: null, $asset);


        $this->em->persist($trade);

        try {
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->session->getFlashBag()->add($errorTitle, 'Unexpected error. Please try again.');
            return false;
        }

        $this->session->getFlashBag()->add($successTitle, 'Trade successfully opened');
        return true;
    }

    private function validateTradeRequest(?User $targetUser, ?Asset $asset, string $errorTitle): bool
    {
        if (!$targetUser) {
            $this->session->getFlashBag()->add($errorTitle, 'User not found');
            return false;
        }

        if (!$asset) {
            $this->session->getFlashBag()->add($errorTitle, 'Asset not found');
            return false;
        }

        return true;
    }

    private function createTrade(
        User $targetUser,
        UserInterface $agent,
        string $position,
        float $lotCount,
        ?float $sl,
        ?float $tp,
        Asset $asset
    ): Trade {
        $lotSize = 10;
        $tradeSize = $lotSize * $lotCount;
        $conversionRate = $this->assetService->getConversionRate($asset, $targetUser->getCurrency());
        $pipValue = $tradeSize * 0.01 * $conversionRate;
        $userMargin = $tradeSize * 0.1 * $conversionRate;
    
        $entryRate = $this->assetService->getCurrentRate($asset, $position);
    
        $trade = new Trade();
        $trade->setUser($targetUser);
        $trade->setAgentId($agent);
        $trade->setPosition($position);
        $trade->setLotCount($lotCount);
        $trade->setStopLoss($sl);
        $trade->setTakeProfit($tp);
        $trade->setStatus(Trade::STATUS_OPEN);
        $trade->setEntryRate($entryRate);
        $trade->setTradeSize($tradeSize);
        $trade->setUsedMargin($userMargin);
        $trade->setDateCreated(new \DateTime());
    
        return $trade;
    }
    

    public function closeTrade(int $id, Request $request)
    {
        $referer = $request->headers->get('referer');
        $trade = $this->em->getRepository(Trade::class)->find($id);

        if (!$trade) {
            $this->session->getFlashBag()->add('close_trade_error', 'Trade not found.');
            return false;
        }

        $asset = $this->assetService->getAssetByName('BTC/USD'); 
        if (!$asset) {
            $this->session->getFlashBag()->add('close_trade_error', 'Asset not found.');
            return false;
        }

        $currentRate = $this->assetService->getCurrentRate($asset, $trade->getPosition());
        $conversionRate = $this->assetService->getConversionRate($asset, $trade->getUser()->getCurrency());
        $pnl = $this->calculatePnl($trade, $currentRate, $conversionRate);
        $margin = $this->calculateMargin($trade, $currentRate, $conversionRate);

        // Обновляем данные сделки
        $trade->setStatus(Trade::STATUS_CLOSED);
        $trade->setCloseRate($currentRate);
        $trade->setDateClose(new \DateTime());
        $trade->setPnl($pnl);
        $trade->setUsedMargin($margin);

        $user = $trade->getUser();
        $oldTotalPnl = $user->getTotalPnl();
        $user->setTotalPnl($oldTotalPnl + $pnl);

        $this->em->flush();

        $this->session->getFlashBag()->add('close_trade_success', "Trade ID: [{$trade->getId()}] was successfully closed.");

        return false;
    }
    public function getAllTradesForUserAndSubordinates(UserInterface $user, array $subordinates): array
    {
        return $this->tradeRepository->getAllForUserAndSubordinates($user, $subordinates);
    }

    private function calculatePipValue(Trade $trade, float $conversionRate): float
    {
        $lotSize = 10; // всегда 10
        return $lotSize * $trade->getLotCount() * 0.01 * $conversionRate;
    }
    
    private function calculatePnl(Trade $trade, float $currentRate, float $conversionRate): float
    {
        $pipValue = $this->calculatePipValue($trade, $conversionRate);
    
        if ($trade->getPosition() === Trade::BUY) {
            return ($currentRate - $trade->getEntryRate()) * $pipValue * 100;
        }
    
        return ($trade->getEntryRate() - $currentRate) * $pipValue * 100;
    }
    
    private function calculateMargin(Trade $trade, float $currentRate, float $conversionRate): float
    {
        $tradeSize = 10 * $trade->getLotCount(); // lot size * lot count
        return $tradeSize * 0.1 * $conversionRate * $currentRate;
    }
    


}