<?php

namespace App\Controller;

use App\Service\TradeService;
use App\Service\AgentAssignmentService;
use App\Service\HierarchyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RoleBasedProfileController extends AbstractController
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

    #[Route('/dashboard', name: 'role_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isRep = $this->isGranted('ROLE_REP');

        if (!$isAdmin && !$isRep) {
            throw new AccessDeniedException('Access denied');
        }

        list($users, $agents) = $this->hierarchyService->getAllSubordinates($user, $isAdmin);
        $trades = $this->tradeService->getAllTradesForUserAndSubordinates($user, $users);
        $repHierarchy = $this->hierarchyService->buildHierarchyTree($user);

        $template = '/dashboard/dashboard.html.twig';

        return $this->render($template, [
            'user' => $user,
            'rep' => $repHierarchy,
            'users' => $users,
            'agents' => $agents,
            'trades' => $trades,
            'route' => $isAdmin ? 'admin' : 'rep',
            'isRoot' => $isAdmin,
        ]);
    }

    #[Route('/assign-agent', name: 'role_assign_agent', methods: ['POST'])]
    public function assignAgent(Request $request): RedirectResponse
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isRep = $this->isGranted('ROLE_REP');

        if (!$isAdmin && !$isRep) {
            throw new AccessDeniedException('Access denied');
        }

        $clientId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');
        $tableName = $request->query->get('tableName');
        $referer = $request->headers->get('referer') ?? $this->generateUrl($isAdmin ? 'role_dashboard' : 'role_dashboard');

        try {
            $message = $this->agentAssignmentService->assignAgent($clientId, $agentId);
            $this->addFlash(($tableName . '_success'), $message);
        } catch (\RuntimeException $e) {
            $this->addFlash(($tableName . '_error'), $e->getMessage());
        }

        return $this->redirect($referer . (($tableName === 'users_tb') ? '#manage-users' : '#manage-agents'));
    }

    #[Route('/open-trade', name: 'role_open_trade', methods: ['POST'])]
    public function openTrade(Request $request, UserInterface $user): RedirectResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REP')) {
            throw new AccessDeniedException('Access denied1');
        }

        $this->tradeService->handleTrade($request, $user);
        $referer = $request->headers->get('referer') ?? $this->generateUrl('role_dashboard');
        return $this->redirect($referer . '#open-trade');
    }

    #[Route('/close-trade/{tradeId}', name: 'role_close_trade', methods: ['POST'])]
    public function closeTrade(int $tradeId, Request $request): RedirectResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REP')) {
            throw new AccessDeniedException('Access denied');
        }

        $this->tradeService->closeTrade($tradeId, $request);
        $referer = $request->headers->get('referer') ?? $this->generateUrl('role_dashboard');
        return $this->redirect($referer . '#tradesTable');
    }
}
