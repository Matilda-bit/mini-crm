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

class AdminProfileController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {
        list($users, $agents) = $this->filterUsersByRole($user);
        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $user, 'status' => 'open']);
         
        return $this->render('/dashboard/admin/admin.html.twig', [
            'controller_name' => 'AdminProfileController',
            'user' => $user,
            'trades' => $trades,
            'users' => $users,
            'agents' => $agents,
        ]);
    }

    private function filterUsersByRole(UserInterface $currentUser): array
    {
        $allUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
        $agents = [];
        $users = [];

        foreach ($allUsers as $userItem) {
            if ($userItem->getId() === $currentUser->getId()) {
                continue;
            }

            if ($userItem->getRole() === 'REP') {
                $agents[] = $userItem;
            } else {
                $users[] = $userItem;
            }
        }

        return [$users, $agents];
    }

    #[Route('/assign-agent', name: 'assign_agent', methods: ['POST'])]
    public function assignAgent(Request $request): RedirectResponse
    {
        $userId = $request->request->get('user_id');
        $agentId = $request->request->get('agent_id');
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
        $agent = $this->getDoctrine()->getRepository(User::class)->find($agentId);

        if ($user && $agent) {
            $user->setAgent($agent);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Agent successfully assigned!');
        } else {
            $this->addFlash('error', 'Error assigning agent!');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
