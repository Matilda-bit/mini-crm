<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\VarDumper\VarDumper;

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {

        $allowedRoles = ['ADMIN'];
        if (!in_array($user->getRole(), $allowedRoles)) {
            throw new AccessDeniedException('Access Denied. [AdminProfileController]');
        }

        list($users, $agents) = $this->getAllSubordinates($user);
        $trades = $this->getAllTradesForUserAndSubordinates($user, $users);

        // dd(['AGENTS1' => $agents, 'USERS1' => $users]);

        return $this->render('/dashboard/admin/admin.html.twig', [
            'controller_name' => 'AdminProfileController',
            'user' => $user,
            'trades' => $trades,
            'users' => $users,
            'agents' => $agents,
        ]);
    }

    private function getAllSubordinates(UserInterface $user): array
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $allUsers = $userRepository->findAll();

        $map = [];
        foreach ($allUsers as $u) {
            $agent = $u->getAgent();
            if ($agent !== null) {
                $map[$agent->getId()][] = $u;
            }
        }

        $agents = [];
        $users = [];
        $queue = [$user];
        $visitedIds = [];

        while (!empty($queue)) {
            $current = array_pop($queue);
            $visitedIds[] = $current->getId();
            $subordinates = $map[$current->getId()] ?? [];

            foreach ($subordinates as $sub) {
                if ($sub->getRole() === 'REP') {
                    if (!in_array($sub, $agents, true)) {
                        $agents[] = $sub;
                        $queue[] = $sub;
                    }
                } elseif ($sub->getRole() === 'USER') {
                    if (!in_array($sub, $users, true)) {
                        $users[] = $sub;
                    }
                }
            }
        }

        if ($user->getRole() === 'ADMIN') {
            foreach ($allUsers as $u) {
                if ($u->getAgent() === null && !in_array($u->getId(), $visitedIds)) {
                    if ($u->getRole() === 'REP') {
                        $agents[] = $u;
                    } elseif ($u->getRole() === 'USER') {
                        $users[] = $u;
                    }
                }
            }
        }

        return [$users, $agents];
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
        //must-have - check role of current user
        $allowedRoles = ['ADMIN', 'REP'];
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
}
