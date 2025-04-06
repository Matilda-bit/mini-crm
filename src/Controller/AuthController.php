<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\FirewallMapInterface;

class AuthController extends AbstractController
{

    private $guardHandler;
    private $firewallMap;

    // Инъекция зависимостей через конструктор
    public function __construct(
        GuardAuthenticatorHandler $guardHandler,
        FirewallMapInterface $firewallMap
    ) {
        $this->guardHandler = $guardHandler;
        $this->firewallMap = $firewallMap;
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

        // Обрабатываем данные формы
        $form->handleRequest($request);

        // Если форма отправлена и валидна
        if ($form->isSubmitted() && $form->isValid()) {
            // Проверяем, существует ли уже такой пользователь
            if ($userRepository->findOneBy(['username' => $user->getUsername()])) {
                $this->addFlash('error', 'Username already exists.');
                return $this->redirectToRoute('app_login_register');
            }

            // Хешируем пароль
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            // Устанавливаем роль по умолчанию
            $user->setLoginTime(new \DateTime());

            $user->setTotalPnl(0);
            $user->setEquity(0);
            $user->setRole('USER');
            $user->setDateCreated(new \DateTime());


            // Если есть ошибки валидации, отобразите их
            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo $error->getMessage();
                }
                // return;
            }
            
            // Отладка: выводим объект перед сохранением
            dump($user);  // Symfony Debugging tool

            dump($user->getRole());


            // Сохраняем пользователя в базе данных
            $em->persist($user);
            $em->flush();

            // Отправляем сообщение об успехе
            $this->addFlash('success', 'Registration successful! You can now log in.');

            // Опционально: автоматически авторизовать пользователя после регистрации
            $this->loginAfterRegistration($user);

            return $this->redirectToRoute('app_dashboard');
        }

        // Рендерим шаблон и передаем форму
        return $this->render('auth/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function loginAfterRegistration(User $user): void
    {
        // Логика для аутентификации пользователя после регистрации
        $this->guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $this->firewallMap->getFirewallConfig('main'),
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

        return $this->redirectToRoute('app_dashboard');
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
