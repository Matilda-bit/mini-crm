<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AgentAssignmentService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function assignAgent(User $user, User $agent): string
    {
        if ($agent->getAgent() === null) {
            throw new \RuntimeException("Assignment denied: please assign an agent to agent [{$agent->getId()}] first.");
        }

        if ($agent->getRole() === 'USER') {
            throw new \RuntimeException("User can’t be in charge of an agent or other user.");
        }

        if ($this->isCircularAssignment($user, $agent)) {
            throw new \RuntimeException("Assignment denied: circular agent relationship detected.");
        }

        if ($user->getAgent() && $user->getRole() !== 'USER') {
            $subordinates = $this->getAllSubordinatesAgents($user);
            foreach ($subordinates as $sub) {
                if ($sub->getId() === $agent->getId()) {
                    throw new \RuntimeException("Assignment denied: agent is a subordinate of the user.");
                }
            }
        }

        $user->setAgent($agent);
        $this->em->flush();

        $this->logger->info("Assigned agent ID {$agent->getId()} to user ID {$user->getId()}");

        return "Agent [ID: {$agent->getId()}] was successfully assigned to user {$user->getUsername()} [ID: {$user->getId()}].";
    }

    private function isCircularAssignment(User $user, User $agent): bool
    {
        $a = $agent;
        while ($a !== null) {
            if ($a->getId() === $user->getId()) {
                return true;
            }
            $a = $a->getAgent();
        }
        return false;
    }

    private function getAllSubordinatesAgents(User $user): array
    {
        $repo = $this->em->getRepository(User::class);
        $allUsers = $repo->createQueryBuilder('u')
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
        $visited = [$user->getId()];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $subs = $map[$current->getId()] ?? [];
            foreach ($subs as $sub) {
                if (in_array($sub->getId(), $visited, true)) continue;
                $visited[] = $sub->getId();
                if ($sub->getRole() === 'REP') {
                    $agents[] = $sub;
                    $queue[] = $sub;
                }
            }
        }

        return $agents;
    }
}
