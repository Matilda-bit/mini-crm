<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:test-subordinates',
    description: 'Проверка иерархии агентов и пользователей для заданного пользователя (например, админа)',
)]
class TestSubordinatesCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->em->getRepository(User::class)->findAll();
    
        // Собираем map [agent_id => [users]]
        $map = [];
        $orphans = [];
        $admins = [];
    
        foreach ($users as $user) {
            if ($user->getAgent()) {
                $map[$user->getAgent()->getId()][] = $user;
            } else {
                if ($user->getRole() === 'ADMIN') {
                    $admins[] = $user;
                } else {
                    $orphans[] = $user; // без агента и не админ
                }
            }
        }
    
        foreach ($admins as $admin) {
            // Прикрепляем "осиротевших" к первому админу
            foreach ($orphans as $orphan) {
                $map[$admin->getId()][] = $orphan;
            }
    
            // Выводим дерево с админом как корнем
            $this->printTree($admin, $map, $output);
        }
    
        return Command::SUCCESS;
    }
    

    private function printTree(User $user, array $map, OutputInterface $output, string $prefix = '', bool $isLast = true): void
    {
        $marker = $prefix === '' ? '' : ($isLast ? '└── ' : '├── ');
        $output->writeln($prefix . $marker . "[{$user->getId()}] {$user->getUsername()} ({$user->getRole()})");

        $children = $map[$user->getId()] ?? [];
        $count = count($children);
        foreach ($children as $index => $child) {
            $isLastChild = $index === $count - 1;
            $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
            $this->printTree($child, $map, $output, $newPrefix, $isLastChild);
        }
    }
}