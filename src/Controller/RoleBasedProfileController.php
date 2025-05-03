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
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getUser();
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


    #[Route('/dashboard/user/{userId}', name: 'dashboard_user', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getUserProfile(
        int $userId, 
        Request $request,
        UserRepository $userRepository
        ): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isRep = $this->isGranted('ROLE_REP');

        if (!$isAdmin && !$isRep) {
            throw new AccessDeniedException('Access denied');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            throw new NotFoundHttpException("User with ID $userId not found.");
        }

        $trades = $this->tradeService->getTradesByUser($user);

        $template = '/dashboard/user/profile.html.twig';
        return $this->render($template, [
            'user' => $user,
            'trades' => $trades,
            'isView' => true
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
            $trade = $this->tradeService->handleTrade($request, $user);

            if (!$trade) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Could not open trade'
                ], 400);
            }
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Trade successfully opened!',
                'trade' => [
                    'id' => $trade->getId(),
                    'user' => $trade->getUser()->getUsername(),
                    'userId' => $trade->getUser()->getId(),
                    'agent' => $user->getUsername(),
                    'position' => $trade->getPosition(),
                    'entryRate' => $trade->getEntryRate(),
                    'lotCount' => $trade->getLotCount(),
                    'status' => $trade->getStatus(),
                    'userCurrency' => $trade->getUser()->getCurrency()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/close-trade/{tradeId}', name: 'role_close_trade', methods: ['POST'])]
    public function closeTrade(int $tradeId, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REP')) {
            throw new AccessDeniedException('Access denied');
        }

        try {
            $this->tradeService->closeTrade($tradeId, $request);
    
            $trade = $this->tradeService->getTradeById($tradeId);
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Trade closed successfully',
                'trade' => [
                    'id' => $trade->getId(),
                    'status' => $trade->getStatus(),
                    'pnl' => number_format($trade->getPnl(), 1, '.', ''),
                    'closeRate' => $trade->getCloseRate(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
