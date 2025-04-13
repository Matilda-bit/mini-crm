<?php

namespace App\Service;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;

class LoggerService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function logAction(int $userId, string $actionName): void
    {
        $log = new Log();
        $log->setUserId($userId);
        $log->setActionName($actionName);
        $log->setDateCreated(new \DateTime());

        $this->em->persist($log);
        $this->em->flush();
    }
}
