<?php

namespace App\Controller;

use App\Entity\Trade;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ProfileController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em, UserInterface $user)
    {

        if (!$user) {
            $this->addFlash('login_error', 'Your session expired, please login again');
            return $this->redirectToRoute('app_login_register');
        }

        $trades = $em->getRepository(Trade::class)->findBy(['user' => $user]);

        $template = '/dashboard/user/profile.html.twig';

        return $this->render($template, [
            'user' => $user,
            'trades' => $trades,
        ]);
    }

}
