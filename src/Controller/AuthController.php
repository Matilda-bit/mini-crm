<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class AuthController extends AbstractController
{

    private $authenticator;
    private UserAuthenticatorInterface $userAuthenticator;

    public function __construct(
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator
    ) {
        $this->userAuthenticator = $userAuthenticator;
        $this->authenticator = $authenticator;
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

        if ($form->isSubmitted() && $form->isValid()) {
            // Проверяем, существует ли уже такой пользователь
            if ($userRepository->findOneBy(['username' => $user->getUsername()])) {
                $this->addFlash('error', 'Username already exists.');
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
                    echo $error->getMessage();
                }
                // return;
            }
            
            // Отладка: выводим объект перед сохранением
            dump($user);  // Symfony Debugging tool

            $em->persist($user);
            $em->flush();


            $this->addFlash('success', 'Registration successful! You can now log in.');
            $this->loginAfterRegistration($request,$user);

            return $this->redirectToDashboard();
        }

        return $this->render('auth/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function loginAfterRegistration(Request $request, User $user): void
    {

        $this->userAuthenticator->authenticateUser(
            $user,
            $this->authenticator,
            $request
        );
    }

   #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $this->addFlash('error', 'Invalid credentials.');
            return $this->redirectToRoute('app_login_register');
        }

        return $this->redirectToDashboard();
    }

    //to check - not in use???
    private function redirectToDashboard(): Response
    {
        $user = $this->getUser();
        $role = $user->getRole();
        // Проверяем роль пользователя и перенаправляем на соответствующую страницу
        if ($role === 'REP') {
            return $this->redirectToRoute('agent_dashboard');
        }
        if ($role === 'ADMIN') {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('user_dashboard');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by Symfony logout system
    }

    #[Route('/', name: 'app_login_register')]
    public function index(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
    
        // Process the form submission (if needed)
        $form->handleRequest($request);
    
        // Render the template with the form view
        return $this->render('auth/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
