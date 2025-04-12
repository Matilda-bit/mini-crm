<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use App\Entity\Asset;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[IsGranted('ROLE_REP')]
class AgentProfileController extends AbstractController
{
    //move constants ? todo
    const STATUS_OPEN = 'open';
    const STATUS_WON = 'won';
    const STATUS_LOSE = 'lose';
    const STATUS_TIE = 'tie';
    const STATUS_CLOSED = 'closed'; 
    const BUY = 'buy';

    #[Route('/agent/dashboard', name: 'agent_dashboard', methods: ['GET'])]
    public function index(UserInterface $user)
    {
        if (!in_array('REP', $user->getRoles())) {
            throw new AccessDeniedException('Access Denied. [AgentProfileController]');
        }
    
        list($users, $agents) = $this->filterUsersAndAgentsByHierarchy($user);
        $trades = $this->getAllTradesForUserAndSubordinates($user, $users);
            
        return $this->render('/dashboard/agent/agent.html.twig', [
            'controller_name' => 'AgentProfileController',
            'user' => $user,
            'trades' => $trades, 
            'users' => $users,
            'agents' => $agents,
        ]);
    }
    //this function must be as a interface for Admins and Agents ? todo
    private function filterUsersAndAgentsByHierarchy(UserInterface $user): array
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        if ($user->getRole() === 'ADMIN') {
            $users = $userRepository->findBy(['role' => 'USER']);
            $agents = $userRepository->findBy(['role' => 'REP']);
        } elseif ($user->getRole() === 'REP') {
            $agents = $this->getSubordinateAgents($user);
            $users = $this->getSubordinateUsers($user, $agents);
        } else {
            $users = [];
            $agents = [];
        }

        return [$users, $agents];
    }

    private function getSubordinateAgents(UserInterface $agent, array &$collected = []): array
    {
        $repo = $this->getDoctrine()->getRepository(User::class);

        $subAgents = $repo->findBy(['agent' => $agent, 'role' => 'REP']);

        foreach ($subAgents as $sub) {
            if (!in_array($sub, $collected, true)) {
                $collected[] = $sub;
                $this->getSubordinateAgents($sub, $collected);
            }
        }

        return $collected;
    }

    private function getSubordinateUsers(UserInterface $agent, array $subAgents): array
    {
        $repo = $this->getDoctrine()->getRepository(User::class);
        $allAgents = array_merge([$agent], $subAgents);

        $users = [];
        foreach ($allAgents as $a) {
            $found = $repo->findBy(['agent' => $a, 'role' => 'USER']);
            $users = array_merge($users, $found);
        }

        return $users;
    }

    private function getAllTradesForUserAndSubordinates(UserInterface $user, array $users): array
    {
        $allUsers = array_merge([$user], $users);

        return $this->getDoctrine()
            ->getRepository(Trade::class)
            ->createQueryBuilder('t')
            ->where('t.user IN (:users)')
            ->setParameter('users', $allUsers)
            ->getQuery()
            ->getResult();
    }

    #[Route('/agent/assign-agent', name: 'rep_assign_agent', methods: ['POST'])]
    public function assignAgent(Request $request, UserInterface $currentUser): RedirectResponse
    {
        //must-have - check role of current user
        $allowedRoles = ['REP'];
        if (!in_array($currentUser->getRole(), $allowedRoles)) {
            throw new AccessDeniedException('You do not have permission to assign agents.');
        }
        
        $userId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
        $agent = $this->getDoctrine()->getRepository(User::class)->find($agentId);
        $isUser = $user->getRole() === 'USER';
        $errorTitle = $isUser ? 'users_tb_error' : 'agent_tb_error';
        $successTitle = $isUser ? 'users_tb_success' : 'agents_tb_success';
        
        if (!$user) {
            $this->addFlash($errorTitle, 'User not found!');
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if (!$agent) {
            $this->addFlash($errorTitle, 'Agent not found!');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($user && $agent) {
            $user->setAgent($agent);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash($successTitle, "Agent [ID: {$agent->getId()}] was successfully assigned to user {$user->getUsername()} [ID: {$user->getId()}].");

        } else {
            $this->addFlash($errorTitle, 'Error assigning agent!  One of the users not found.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/open-trade', name: 'open_trade', methods: ['POST'])]
    public function openTrade(Request $request, EntityManagerInterface $em, UserInterface $user): RedirectResponse
    {
        
        $targetUserId = $request->request->get('target_user'); 
        $position = $request->request->get('position');
        $lotCount = (float) $request->request->get('lot_count');
        $sl = $request->request->get('sl');
        $tp = $request->request->get('tp');
        $errorTitle = 'open_trade_error';
        $successTitle = 'open_trade_success';

        $targetUser = $em->getRepository(User::class)->find($targetUserId); 

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }

        $asset = $em->getRepository(Asset::class)->findOneBy(['assetName' => 'BTC/USD']);
        
        if (!$asset) {
            $this->addFlash($errorTitle, 'Asset not found');
            return $this->redirect($request->headers->get('referer'));
        }

        $entryRate = $position === self::BUY ? $asset->getAsk() : $asset->getBid();

        $trade = new Trade();
        $trade->setUser($targetUser);
        $trade->setPosition($position);
        $trade->setLotCount($lotCount);
        $trade->setStopLoss($sl ?: null);
        $trade->setTakeProfit($tp ?: null);
        $trade->setStatus(self::STATUS_OPEN);
        $trade->setEntryRate($entryRate);
        $trade->setDateCreated(new \DateTime());

        $em->persist($trade);
        $em->flush();

        $this->addFlash($successTitle, 'Trade successfully opened');

        return $this->redirect($request->headers->get('referer'));
    }


    #[Route('/close-trade/{id}', name: 'close_trade', methods: ['POST'])]
    public function closeTrade(int $id, Request $request, EntityManagerInterface $em)
    {
        $trade = $em->getRepository(Trade::class)->find($id);
    
        if (!$trade) {
            $this->addFlash('close_trade_error', 'Trade not found.');
            return $this->redirect($request->headers->get('referer'));
        }
    
        $asset = $this->getDoctrine()->getRepository(Asset::class)->findOneBy(['assetName' => 'BTC/USD']);
        $currentRate = ($trade->getPosition() === self::BUY) ? $asset->getBid() : $asset->getAsk();
    
        $pnl = 0;
        if ($trade->getPosition() === self::BUY) {
            $pnl = ($currentRate - $trade->getEntryRate()) * $trade->getLotCount() * 0.01;
        } else {
            $pnl = ($trade->getEntryRate() - $currentRate) * $trade->getLotCount() * 0.01;
        }
    
        $userCurrency = $trade->getUser()->getCurrency();
        $margin = $trade->getTradeSize() * 0.1 * $currentRate;
    
        $trade->setStatus(self::STATUS_CLOSED); 
        $trade->setCloseRate($currentRate);
        $trade->setDateClose(new \DateTime());
        $trade->setPnl($pnl);
        $trade->setUsedMargin($margin);
    
        $em->flush();
    
        $this->addFlash('close_trade_success', "Trade ID: [{$trade->getId()}] was successfully closed.");

        return $this->redirect($request->headers->get('referer'));
    }
    
}
