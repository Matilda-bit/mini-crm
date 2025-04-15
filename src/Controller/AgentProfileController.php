<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use App\Service\TradeService;
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

    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;
    }

    #[Route('/agent/dashboard', name: 'agent_dashboard', methods: ['GET'])]
    public function index(UserInterface $user)
    {
    
        list($users, $agents) = $this->filterUsersAndAgentsByHierarchy($user);
        $trades = $this->getAllTradesForUserAndSubordinates($user, $users);
        $repHierarchy = $this->buildHierarchyTree($user);
            
        return $this->render('/dashboard/agent/agent.html.twig', [
            'controller_name' => 'AgentProfileController',
            'user' => $user,
            'trades' => $trades, 
            'users' => $users,
            'agents' => $agents,
            'rep' => $repHierarchy,
        ]);
    }


    private function buildHierarchyTree(UserInterface $user): array
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $allUsers = $userRepository->findAll();
    
        $map = [];
        $nodes = [];
        foreach ($allUsers as $u) {
            $nodes[$u->getId()] = [
                'user' => [
                    'id' => $u->getId(),
                    'username' => $u->getUsername(),
                    'role' => $u->getRole()
                ],
                'children' => []
            ];
            if ($u->getAgent()) {
                $map[$u->getAgent()->getId()][] = $u->getId(); // связь id → id
            }
        }
    
        $visited = [];
    
        $buildTree = function ($id) use (&$buildTree, &$map, &$nodes, &$visited) {
            if (in_array($id, $visited, true)) {
                return null;
            }
            $visited[] = $id;
    
            $node = $nodes[$id];
            $childrenIds = $map[$id] ?? [];
    
            foreach ($childrenIds as $childId) {
                $childNode = $buildTree($childId);
                if ($childNode) {
                    $node['children'][] = $childNode;
                }
            }
    
            return $node;
        };
    
        return [$buildTree($user->getId())]; // возвращаем массив с корнем
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

        $userRepo = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepo->find($userId);
        $agent = $userRepo->find($agentId);
        
        $isUser = $user->getRole() === 'USER';
        $errorTitle = $isUser ? 'users_tb_error' : 'agent_tb_error';
        $successTitle = $isUser ? 'users_tb_success' : 'agents_tb_success';
        
        if (!$user || !$agent) {
            $this->addFlash($errorTitle, 'User or agent not found.');
            return new RedirectResponse($this->generateUrl('admin_dashboard'));
        }
        if ($agent->getRole() === 'USER') {
            $this->addFlash($errorTitle, 'User can’t be in charge of an agent or other user.');
            return new RedirectResponse($this->generateUrl('admin_dashboard'));
        }

        $a = $agent;
        while ($a !== null) {
            if ($a->getId() === $user->getId()) {
                $this->addFlash($errorTitle, 'Assignment denied: circular agent relationship detected.');
                return new RedirectResponse($this->generateUrl('admin_dashboard'));
            }
            $a = $a->getAgent();
        }
        
        $user->setAgent($agent);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash($successTitle, "Agent [ID: {$agent->getId()}] was successfully assigned to user {$user->getUsername()} [ID: {$user->getId()}].");

        return $this->redirectToRoute('agent_dashboard');
    }

    #[Route('/open-trade', name: 'open_trade', methods: ['POST'])]
    public function openTrade(Request $request, EntityManagerInterface $em, UserInterface $user): RedirectResponse
    {
        $this->tradeService->handleTrade($request, $em, $user);
        $referer = $request->headers->get('referer');
        return $this->redirect($referer . '#open-trade');
    }


    #[Route('/close-trade/{id}', name: 'close_trade', methods: ['POST'])]
    public function closeTrade(int $id, Request $request, EntityManagerInterface $em)
    {
        $referer = $request->headers->get('referer');
        $this->tradeService->closeTrade($id, $request, $em);
        return $this->redirect($referer . '#tradesTable');
        
    }
    
}
