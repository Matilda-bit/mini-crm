<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use App\Repository\UserRepository;
use App\Service\TradeService;
use App\Service\AgentAssignmentService;
use App\Service\HierarchyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

// use Symfony\Component\VarDumper\VarDumper; // for debugging

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractController
{
    private AgentAssignmentService $agentAssignmentService;
    private TradeService $tradeService;
    private HierarchyService $hierarchyService;
    private UserRepository $userRepository;

    public function __construct(
        AgentAssignmentService $agentAssignmentService,
        TradeService $tradeService,
        HierarchyService $hierarchyService,
        UserRepository $userRepository
    ) {
        $this->agentAssignmentService = $agentAssignmentService;
        $this->tradeService = $tradeService;
        $this->hierarchyService = $hierarchyService;
        $this->userRepository = $userRepository; // maybe move it into businness logic - service? or validation? or add validation into service?
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
    public function assignAgent(Request $request, UserInterface $currentUser): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $clientId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');

        $client = $this->userRepository->find($userId);
        $agent = $this->userRepository->find($agentId);
        
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500

        if (!$client) {
            $this->addFlash('agents_tb_error', 'User not found.');
            return $this->redirect($referer . '#manage-agents'); 
        }

        $isUser = $client->getRole() === 'USER';
        $errorTitle = $isUser ? 'users_tb_error' : 'agents_tb_error';
        $successTitle = $isUser ? 'users_tb_success' : 'agents_tb_success';

        if (!$agent) {
            if($isUser) {
                $this->addFlash($errorTitle, 'Agent not found.');
                return $this->redirect($referer . '#manage-users');
            } else {
                $this->addFlash($errorTitle, 'Agent not found.');
                return $this->redirect($referer . '#manage-agents');
            }
        }

        try {
            $message = $this->agentAssignmentService->assignAgent($client, $agent);
            $this->addFlash($successTitle, $message);
        } catch (\RuntimeException $e) {
            $this->addFlash($errorTitle, $e->getMessage());
        }

        return $this->redirect($referer . ($isUser ? '#manage-users' : '#manage-agents'));
    }

    #[Route('/open-trade', name: 'open_trade', methods: ['POST'])]
    public function openTrade(Request $request, EntityManagerInterface $em, UserInterface $user): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->tradeService->handleTrade($request, $em, $user);
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500
        return $this->redirect($referer . '#open-trade');
    }


    #[Route('/close-trade/{id}', name: 'close_trade', methods: ['POST'])]
    public function closeTrade(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $referer = $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard'); // to prevent error 500
        $this->tradeService->closeTrade($id, $request, $em);
        return $this->redirect($referer . '#tradesTable');
        
    }
    
}
