<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class HierarchyService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllSubordinates(UserInterface $user, bool $withNull): array
    {
        $allUsers = $this->userRepository->getAllUsersWithAgents();
    
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
    
        $agents = [$user];
        $users = [];
        $queue = [$user];
        $visitedIds = [$user->getId()];
    
    
        while (!empty($queue)) {
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
    
        if ($user->getRole() === 'ADMIN') {
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

    public function buildHierarchyTree(UserInterface $user): array
    {
        $allUsers = $this->userRepository->getAllUsersWithAgents(); //left join

        $map = [];
        $nodes = [];
        $orphans = [];

        foreach ($allUsers as $u) {
            $nodes[$u->getId()] = [
                'user' => [
                    'id' => $u->getId(),
                    'username' => $u->getUsername(),
                    'role' => $u->getRole(),
                    'agentNull' => $u->getAgent() === null,
                ],
                'children' => []
            ];

            if ($u->getAgent()) {
                $map[$u->getAgent()->getId()][] = $u->getId();
            } else {
                $orphans[] = $u->getId();
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

        $root = $buildTree($user->getId());

        // ADMIN can also see orphan branches
        if ($user->getRole() === 'ADMIN') {
            foreach ($orphans as $orphanId) {
                if (!in_array($orphanId, $visited, true) && $orphanId !== $user->getId()) {
                    $root['children'][] = $nodes[$orphanId];
                }
            }
        }

        return [$root];
    }
    
}
