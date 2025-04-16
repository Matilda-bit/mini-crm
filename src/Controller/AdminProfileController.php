<?php

namespace App\Controller;

use App\Service\TradeService;
use App\Service\AgentAssignmentService;
use App\Service\HierarchyService;

use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Security;

// use Symfony\Component\VarDumper\VarDumper; // for debugging

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractController
{
    private AgentAssignmentService $agentAssignmentService;
    private TradeService $tradeService;
    private HierarchyService $hierarchyService;

    public function __construct(
        AgentAssignmentService $agentAssignmentService,
        TradeService $tradeService,
        HierarchyService $hierarchyService,
    ) {
        $this->agentAssignmentService = $agentAssignmentService;
        $this->tradeService = $tradeService;
        $this->hierarchyService = $hierarchyService;
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        list($users, $agents) = $this->hierarchyService->getAllSubordinates($user, true);
        $trades = $this->tradeService->getAllTradesForUserAndSubordinates($user, $users);
        $repHierarchy = $this->hierarchyService->buildHierarchyTree($user);

        return $this->render('/dashboard/admin/admin.html.twig', [
            'controller_name' => 'AdminProfileController',
            'user' => $user,
            'trades' => $trades,
            'users' => $users,
            'agents' => $agents,
            'rep' => $repHierarchy,
        ]);
    }

    #[Route('/admin/assign-agent', name: 'admin_assign_agent', methods: ['POST'])]
    public function assignAgent(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $clientId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');
        $tableName = $request->query->get('tableName');//agents_tb or users_tb
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500

        try {
            $message = $this->agentAssignmentService->assignAgent($clientId, $agentId);

            $this->addFlash(($tableName . '_success'), $message);
        } catch (\RuntimeException $e) {
            $this->addFlash(($tableName . '_error'), $e->getMessage());
        }

        return $this->redirect($referer . (($tableName === 'users_tb') ? '#manage-users' : '#manage-agents'));
    }

    #[Route('/admin/open-trade', name: 'admin_open_trade', methods: ['POST'])]
    public function openTrade(Request $request, UserInterface $user): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->tradeService->handleTrade($request, $user);
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500
        return $this->redirect($referer . '#open-trade');
    }


    #[Route('/admin/close-trade/{tradeId}', name: 'admin_close_trade', methods: ['POST'])]
    public function closeTrade(int $tradeId, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500
        $this->tradeService->closeTrade($tradeId, $request);
        return $this->redirect($referer . '#tradesTable');
        
    }
    
}
