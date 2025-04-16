<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AgentAssignmentService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function assignAgent(int $userId, int $agentId): string
    {

        $client = $this->em->getRepository(User::class)->find($userId);
        if (!$client) {
            throw new \RuntimeException('User not found.');
        }

        $currentAgentId = $client->getAgent()?->getId();

        if ($currentAgentId === $agentId) {
            return sprintf(
                'The user "%s" [ID: %d] is already assigned to Agent [ID: %d].',
                $client->getUsername(),
                $client->getId(),
                $agentId
            );
        }
    
        $agent = $this->em->getRepository(User::class)->find($agentId);
        if (!$agent) {
            throw new \RuntimeException('Agent not found.');
        }

        $agentRole = $agent->getAgent();

        if ($agentRole === null) {
            throw new \RuntimeException("Assignment denied: please assign an agent to agent [{$agent->getId()}] first.");
        }

        if ($agentRole === 'USER') {
            throw new \RuntimeException("User can’t be in charge of an agent or other user.");
        }

        if ($this->isCircularAssignment($client, $agent)) {
            throw new \RuntimeException("Assignment denied: circular agent relationship detected.");
        }

        if ($client->getAgent() && $client->getRole() !== 'USER') {
            $subordinates = $this->getAllSubordinatesAgents($client);
            foreach ($subordinates as $sub) {
                if ($sub->getId() === $agent->getId()) {
                    throw new \RuntimeException("Assignment denied: agent is a subordinate of the user.");
                }
            }
        }

        $client->setAgent($agent);
        $this->em->flush();

        $this->logger->info("Assigned agent ID {$agent->getId()} to user ID {$client->getId()}");

        return "Agent [ID: {$agent->getId()}] was successfully assigned to user {$client->getUsername()} [ID: {$client->getId()}].";
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
