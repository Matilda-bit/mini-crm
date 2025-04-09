<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


class AgentProfileController extends AbstractController
{

    #[Route('/agent/dashboard', name: 'agent_dashboard', methods: ['GET'])]
    public function index(UserInterface $user)
    {
        if (!in_array('REP', $user->getRoles())) {
            throw new AccessDeniedException('Access Denied.');
        }
   
        list($users, $agents) = $this->filterUsersAndAgentsByHierarchy($user);
        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $user, 'status' => 'open']);
        
        return $this->render('/dashboard/agent/agent.html.twig', [
            'controller_name' => 'AgentProfileController',
            'user' => $user,
            'trades' => $trades,
            'users' => $users,
            'agents' => $agents,
        ]);
    }
    //this function must be as a interface for Admins and Agents
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
}
