<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use App\Service\TradeService;
use App\Service\AgentAssignmentService;
use App\Service\HierarchyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_REP')]
class AgentProfileController extends AbstractController
{
    public function __construct(
        AgentAssignmentService $agentAssignmentService,
        TradeService $tradeService,
        HierarchyService $hierarchyService
    ) {
        $this->agentAssignmentService = $agentAssignmentService;
        $this->tradeService = $tradeService;
        $this->hierarchyService = $hierarchyService;
    }

    #[Route('/agent/dashboard', name: 'agent_dashboard', methods: ['GET'])]
    public function index(UserInterface $user)
    {
        $this->denyAccessUnlessGranted('ROLE_REP');
        list($users, $agents) = $this->hierarchyService->getAllSubordinates($user, false);

        $trades = $this->tradeService->getAllTradesForUserAndSubordinates($user, $users);
        $repHierarchy = $this->hierarchyService->buildHierarchyTree($user);
            
        return $this->render('/dashboard/agent/agent.html.twig', [
            'controller_name' => 'AgentProfileController',
            'user' => $user,
            'trades' => $trades, 
            'users' => $users,
            'agents' => $agents,
            'rep' => $repHierarchy,
        ]);
    }

    #[Route('/agent/assign-agent', name: 'rep_assign_agent', methods: ['POST'])]
    public function assignAgent(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_REP');

        $clientId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');
        $tableName = $request->query->get('tableName');//agents_tb or users_tb

        $referer = $request->headers->get('referer') ?? $this->generateUrl('agent_dashboard'); // to prevent error 500

        try {
            $message = $this->agentAssignmentService->assignAgent($clientId, $agentId);
            $this->addFlash(($tableName . '_success'), $message);
        } catch (\RuntimeException $e) {
            $this->addFlash(($tableName . '_error'), $e->getMessage());
        }

        return $this->redirect($referer . (($tableName === 'users_tb') ? '#manage-users' : '#manage-agents'));
    }

    #[Route('/open-trade', name: 'rep_open_trade', methods: ['POST'])]
    public function openTrade(Request $request, UserInterface $user): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_REP');
        $this->tradeService->handleTrade($request, $user);
        $referer = $request->headers->get('referer') ?? $this->generateUrl('agent_dashboard'); // to prevent error 500
        return $this->redirect($referer . '#open-trade');
    }


    #[Route('/close-trade/{tradeId}', name: 'rep_close_trade', methods: ['POST'])]
    public function closeTrade(int $tradeId, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_REP');
        $referer = $request->headers->get('referer') ?? $this->generateUrl('agent_dashboard'); // to prevent error 500
        $this->tradeService->closeTrade($tradeId, $request);
        return $this->redirect($referer . '#tradesTable');
        
    }
    
}
