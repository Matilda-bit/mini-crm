<?php

namespace App\Controller;

use App\Service\TradeService;
use App\Service\AgentAssignmentService;
use App\Service\HierarchyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository; 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoleBasedProfileController extends AbstractController
{

    public function __construct(
        private AgentAssignmentService $agentAssignmentService,
        private TradeService $tradeService,
        private HierarchyService $hierarchyService,
        private readonly UserRepository $userRepository,
    ) {
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
    public function assignAgent(Request $request): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isRep = $this->isGranted('ROLE_REP');

        if (!$isAdmin && !$isRep) {
            throw new AccessDeniedException('Access denied');
        }

        $clientId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');

        try {
            $message = $this->agentAssignmentService->assignAgent($clientId, $agentId);

            $agent = $this->userRepository->find($agentId);
            return new JsonResponse([
                'success' => true,
                'userId' => $clientId,
                'newAgent' => [
                    'id' => $agent->getId(),
                    'username' => $agent->getUsername(),
                ],
                'message' => $message,
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/open-trade', name: 'role_open_trade', methods: ['POST'])]
    public function openTrade(Request $request, UserInterface $user): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REP')) {
            throw new AccessDeniedException('Access denied');
        }

        try {
            $this->tradeService->handleTrade($request, $user);
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Trade successfully opened!'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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
