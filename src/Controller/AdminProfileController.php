<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
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
        LoggerService $loggerService
    ) {
        $this->loggerService = $loggerService;
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {

        if ($user->getRole()!== 'ADMIN') {
            throw new AccessDeniedException('Access Denied. [AdminProfileController]');
        }

        list($users, $agents) = $this->getAllSubordinates($user, true);
        $trades = $this->getAllTradesForUserAndSubordinates($user, $users);
        $repHierarchy = $this->buildHierarchyTree($user);

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


    private function getAllSubordinates(UserInterface $user, bool $withNull): array
    {
    
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $allUsers = $userRepository->findAll();
    
        $map = [];
        $orphans = [];
        foreach ($allUsers as $u) {
            $agent = $u->getAgent();
            if ($agent !== null) {
                $map[$agent->getId()][] = $u;
            } elseif ($u->getId() !== $user->getId()) {
                $orphans[] = $u;
            }
        }

        $agents = [];
        $users = [];
        $queue = [$user];
        $visitedIds = [$user->getId()];
    
        foreach ($orphans as $u) {
            $uid = $u->getId();
            if (!in_array($uid, $visitedIds, true)) {
                $visitedIds[] = $uid;
                $queue[] = $u;
            }
        }
    
        while (!empty($queue)) {
            /** @var UserInterface $current */
            $current = array_shift($queue);
            $subordinates = $map[$current->getId()] ?? [];
    
            foreach ($subordinates as $sub) {
                $subId = $sub->getId();
                if (in_array($subId, $visitedIds, true)) {
                    continue;
                }
    
                $visitedIds[] = $subId;
    
                if ($sub->getRole() === 'REP') {
                    $agents[] = $sub;
                    $queue[] = $sub;
                } elseif ($sub->getRole() === 'USER') {
                    $users[] = $sub;
                }
            }
        }
        if($withNull){
            foreach ($orphans as $u) {
                if ($u->getRole() === 'REP' && !in_array($u, $agents, true)) {
                    $agents[] = $u;
                } elseif ($u->getRole() === 'USER' && !in_array($u, $users, true)) {
                    $users[] = $u;
                }
            }
        }

        return [$users, $agents];
    }

    private function getAllSubordinatesAgents(UserInterface $user): array
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $allUsers = $userRepository->createQueryBuilder('u')
        ->where('u.role IN (:roles)')
        ->setParameter('roles', ['ADMIN', 'REP'])
        ->getQuery()
        ->getResult();

        $map = [];
        
        foreach ($allUsers as $u) {
            $agent = $u->getAgent();
            if ($agent !== null) {
                $map[$agent->getId()][] = $u;
            } 
        }
    
        $agents = [];
        $queue = [$user];
        $visitedIds = [$user->getId()];
    
        while (!empty($queue)) {
            /** @var UserInterface $current */
            $current = array_shift($queue);
            $subordinates = $map[$current->getId()] ?? [];
            foreach ($subordinates as $sub) {
                $subId = $sub->getId();
                if (in_array($subId, $visitedIds, true)) {
                    continue;
                }
    
                $visitedIds[] = $subId;
    
                if ($sub->getRole() === 'REP') {
                    $agents[] = $sub;
                    $queue[] = $sub;
                } 
            }
        }

        return $agents;
    }



    private function getAllTradesForUserAndSubordinates(UserInterface $user, array $users): array
    {
        if ($user->getRole() === 'ADMIN') {
            // Админ видит все трейды без ограничений
            return $this->getDoctrine()
                ->getRepository(Trade::class)
                ->findAll();
        }
    
        $allUsers = array_merge([$user], $users);
    
        return $this->getDoctrine()
            ->getRepository(Trade::class)
            ->createQueryBuilder('t')
            ->where('t.user IN (:users)')
            ->setParameter('users', $allUsers)
            ->getQuery()
            ->getResult();
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
            $this->addFlash($errorTitle, 'User or agent not found.');
            return $redirect;
        }

        $isUser = $user->getRole() === 'USER';
        $errorTitle = $isUser ? 'users_tb_error' : 'agent_tb_error';
        $successTitle = $isUser ? 'users_tb_success' : 'agents_tb_success';

        if ($agent->getRole() === 'USER') {
            $this->addFlash($errorTitle, 'User can’t be in charge of an agent or other user.');
            return $redirect;
        }

        $a = $agent;
        while ($a !== null) {
            if ($a->getId() === $user->getId()) {
                $this->addFlash($errorTitle, 'Assignment denied: circular agent relationship detected.');
                return $redirect;
            }
            $a = $a->getAgent();
        }

        if($user->getAgent() && $user->getRole() !== 'USER'){
            $allSubs = $this->getAllSubordinatesAgents($user);
            foreach ($allSubs as $sub) {
                if ($sub->getId() === $agent->getId()) {
                    $this->addFlash($errorTitle, 'Assignment denied: agent is a subordinate of the user.');
                    return $redirect;
                }
            }
        }
        
        $user->setAgent($agent);
        $this->getDoctrine()->getManager()->flush();
        
        $this->addFlash($successTitle, "Agent [ID: {$agent->getId()}] was successfully assigned to user {$user->getUsername()} [ID: {$user->getId()}].");
        $this->loggerService->logAction(
            $currentUser->getId(),
            sprintf('Assigned agent ID %d to %s ID %d', $agent->getId(),  $user->getRole() === 'REP' ? 'agent' : 'user', $user->getId())
        );
        return $redirect;
    }


}
