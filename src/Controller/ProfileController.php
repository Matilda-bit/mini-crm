<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Trade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
//todo: refactor userController
class ProfileController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function index(UserInterface $user)
    {
        // Получаем данные пользователя
        $userData = $this->getDoctrine()->getRepository(User::class)->find($user->getId());
        
        // Получаем открытые сделки пользователя
        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $userData, 'status' => 'open']);
        
        return $this->render('profile/profile.html.twig', [
            'user' => $userData,
            'trades' => $trades,
        ]);
    }


    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(UserInterface $user)
    {
        // Получаем данные пользователя
        $userData = $this->getDoctrine()->getRepository(User::class)->find($user->getId());
        
        // Получаем открытые сделки пользователя
        $trades = $this->getDoctrine()->getRepository(Trade::class)->findBy(['user' => $userData, 'status' => 'open']);
        
        return $this->render('profile/profile.html.twig', [
            'user' => $userData,
            'trades' => $trades,
        ]);
    }

}
