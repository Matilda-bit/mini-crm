<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
//todo: refactor userController?

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function index()
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('login_error', 'Your session expired, please login again');
            return $this->redirectToRoute('app_login_register');
        }

        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $user]);

        $template = '/dashboard/user/profile.html.twig';

        // dd($trades);

        return $this->render($template, [
            'user' => $user,
            'trades' => $trades,
        ]);
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(UserInterface $user)
    {
        $userData = $this->getDoctrine()->getRepository(User::class)->find($user->getId());
        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $userData, 'status' => 'open']);
        
        return $this->render('dashboard/user/profile.html.twig', [
            'user' => $userData,
            'trades' => $trades,
        ]);
    }

}
