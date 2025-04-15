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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\VarDumper\VarDumper;
use App\Service\LoggerService;

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractController
{

    public function __construct(
        LoggerService $loggerService,
        AgentAssignmentService $agentAssignmentService,
        TradeService $tradeService,
        HierarchyService $hierarchyService
    ) {
        $this->loggerService = $loggerService;
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

        // dd(['AGENTS1' => $agents, 'USERS1' => $users]);

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
        //must-have - check role of current user \/
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $userId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');

        $userRepo = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepo->find($userId);
        $agent = $userRepo->find($agentId);

        $redirect = new RedirectResponse($this->generateUrl('admin_dashboard'));
        
        if (!$user || !$agent) {
            $this->addFlash('users_tb_error', 'User or agent not found.');//we can't know which table here depens this error ..
            return $redirect;
        }

        $clientRole = $user->getRole();
        $isUser = $clientRole === 'USER';

        $errorTitle = $isUser ? 'users_tb_error' : 'agent_tb_error';
        $successTitle = $isUser ? 'users_tb_success' : 'agents_tb_success';

        try {
            $message = $this->agentAssignmentService->assignAgent($user, $agent);
            $this->addFlash($successTitle, $message);
        } catch (\RuntimeException $e) {
            $this->addFlash($errorTitle, $e->getMessage());
        }

        return $redirect;
    }


    #[Route('/open-trade', name: 'open_trade', methods: ['POST'])]
    public function openTrade(Request $request, EntityManagerInterface $em, UserInterface $user): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->tradeService->handleTrade($request, $em, $user);
        $referer = $request->headers->get('referer');
        return $this->redirect($referer . '#open-trade');
    }


    #[Route('/close-trade/{id}', name: 'close_trade', methods: ['POST'])]
    public function closeTrade(int $id, Request $request, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $referer = $request->headers->get('referer');
        $this->tradeService->closeTrade($id, $request, $em);
        return $this->redirect($referer . '#tradesTable');
        
    }
    


}
