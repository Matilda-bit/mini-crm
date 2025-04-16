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
    const STATUS_OPEN = 'open';
    const STATUS_WON = 'won';
    const STATUS_LOSE = 'lose';
    const STATUS_TIE = 'tie';
    const STATUS_CLOSED = 'closed'; 


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
        if (!$targetUser) {
            $this->session->getFlashBag()->add($errorTitle, 'User not found');
            return false;
        }

        $asset = $this->assetService->getAssetByName($assetName);
        if (!$asset) {
            $this->session->getFlashBag()->add($errorTitle, 'Asset not found');
            return false;
        }


        $entryRate = $this->assetService->getCurrentRate($asset, $position);

        $lotSize = 10;
        $userCurrency = $targetUser->getCurrency();
        $tradeSize = $lotSize * $lotCount;

        $conversionRate = $this->assetService->getConversionRate($asset, $userCurrency); 

        $pipValue = $tradeSize * 0.01 * $conversionRate;
        $userMargin = $tradeSize * 0.1 * $conversionRate;

        $trade = new Trade();
        $trade->setUser($targetUser);
        $trade->setAgentId($user); // opened_by_agent_id / null - set current user
        $trade->setPosition($position);//buy / sell
        $trade->setLotCount($lotCount);
        $trade->setStopLoss($sl ?: null);
        $trade->setTakeProfit($tp ?: null);
        $trade->setStatus(Trade::STATUS_OPEN);
        $trade->setEntryRate($entryRate);
        $trade->setTradeSize($tradeSize);
        $trade->setUsedMargin($userMargin);
        $trade->setDateCreated(new \DateTime());

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

        // Текущая цена для расчета прибыли
        $currentRate = $this->assetService->getCurrentRate($asset, $trade->getPosition());

        // Вычисление прибыли или убытка (P&L)
        $pnl = 0;
        if ($trade->getPosition() === Trade::BUY) {
            $pnl = ($currentRate - $trade->getEntryRate()) * $trade->getLotCount() * 0.01;
        } else {
            $pnl = ($trade->getEntryRate() - $currentRate) * $trade->getLotCount() * 0.01;
        }

        // Вычисление маржи
        $userCurrency = $trade->getUser()->getCurrency();
        $margin = $trade->getTradeSize() * 0.1 * $currentRate;

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
        if ($user->getRole() === 'ADMIN') {
            return $this->tradeRepository->findAll();
        }

        $allUsers = array_merge([$user], $subordinates);
        return $this->tradeRepository->findByUsers($allUsers);
    }


}