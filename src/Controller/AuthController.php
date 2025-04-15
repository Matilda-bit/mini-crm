<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Service\LoggerService;

class AuthController extends AbstractController
{

    private $authenticator;
    private UserAuthenticatorInterface $userAuthenticator;

    public function __construct(
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
        LoggerService $loggerService
    ) {
        $this->userAuthenticator = $userAuthenticator;
        $this->authenticator = $authenticator;
        $this->loggerService = $loggerService;
    }


    #[Route('/register', name: 'app_register', methods: ['POST', 'GET'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $errorTitle = 'login_error';
        $successTitle = 'login_success' ;

        if ($form->isSubmitted() && $form->isValid()) {
            // Проверяем, существует ли уже такой пользователь
            if ($userRepository->findOneBy(['username' => $user->getUsername()])) {
                $this->addFlash($errorTitle, 'Username already exists.');
                return $this->redirectToRoute('app_login_register');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
            $username = $user->getUsername();
            if (strpos($username, 'agent') === 0) {
                $user->setRole('REP');
            } elseif (strpos($username, 'admin') === 0) {
                $user->setRole('ADMIN');
            } else {
                $user->setRole('USER');
            }

            $user->setLoginTime(new \DateTime());
            $user->setTotalPnl(0);
            $user->setEquity(0);
            $user->setDateCreated(new \DateTime());

            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('login_error', $error->getMessage());
                }
                return $this->redirectToRoute('app_login_register');
            }

            $em->persist($user);
            $em->flush();

            $this->loggerService->logAction($user->getId(), 'register');
            $this->addFlash($successTitle, 'Registration successful! You can now log in.');
            $this->loginAfterRegistration($request,$user);

            return $this->redirectToRoute($this->getRouteByRole($user->getRoles()[0]));
        }

        return $this->render('auth/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function getRouteByRole(string $role): string
    {
        return match ($role) {
            'ROLE_ADMIN' => 'admin_dashboard',
            'ROLE_REP' => 'agent_dashboard',
            'ROLE_USER' => 'user_dashboard',
        };
    }

    private function loginAfterRegistration(Request $request, User $user): void
    {

        $this->userAuthenticator->authenticateUser(
            $user,
            $this->authenticator,
            $request
        );
    }

    #[Route('/login', name: 'app_login')]
    public function login(): void {

    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by Symfony logout system
    }

    #[Route('/', name: 'app_login_register')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
    
        $form->handleRequest($request);
    
        return $this->render('auth/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
