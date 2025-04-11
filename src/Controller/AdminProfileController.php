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


class AdminProfileController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {

        if (!in_array('ADMIN', $user->getRoles())) {
            throw new AccessDeniedException('Access Denied.');
        }
        list($users, $agents) = $this->filterUsersByRole($user);
        if (!in_array($user, $agents, true)) {
            $agents[] = $user;
        }
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
        
        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if (!$agent) {
            $this->addFlash('error', 'Agent not found!');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($user && $agent) {
            $user->setAgent($agent);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Agent successfully assigned!');
        } else {
            $this->addFlash('error', 'Error assigning agent!  One of the users not found.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
