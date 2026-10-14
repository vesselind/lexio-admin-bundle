<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\Contract\User\UserEntityInterface;
use Lexio\AdminBundle\Contract\User\UserInvitationInterface;
use Lexio\AdminBundle\Controller\Admin\UserController;
use Lexio\AdminBundle\Filter\BaseFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class UserControllerTest extends TestCase
{
    #[DataProvider('invalidUsers')]
    public function test_id_actions_return_not_found_for_missing_or_incompatible_users(string $action, ?object $user): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('find')->with(42)->willReturn($user);
        $manager->expects($this->once())->method('getRepository')->with(UserEntityInterface::class)->willReturn($repository);
        $manager->expects($this->never())->method('flush');
        $request = Request::create('/admin/user/42/' . $action);
        $controller = $this->controller($manager, $request);

        $this->expectException(NotFoundHttpException::class);

        if ($action === 'update') {
            $controller->update(42, $this->createStub(FormContext::class));

            return;
        }

        $controller->{$action}(42, $request);
    }

    /** @return iterable<string, array{string, ?object}> */
    public static function invalidUsers(): iterable
    {
        foreach (['update', 'delete', 'roles'] as $action) {
            yield $action . ' missing' => [$action, null];
            yield $action . ' incompatible' => [$action, new \stdClass()];
        }
    }

    #[DataProvider('submittedRoles')]
    public function test_roles_preserve_only_allowed_unique_strings_and_the_required_user_role(mixed $submitted, array $expected): void
    {
        $user = $this->createMock(UserEntityInterface::class);
        $user->method('getId')->willReturn(42);
        $user->expects($this->once())->method('setRoles')->with($expected)->willReturnSelf();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('find')->with(42)->willReturn($user);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects($this->once())->method('persist')->with($user);
        $manager->expects($this->once())->method('flush');
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())->method('generate')->with('admin.user.roles', ['id' => 42])->willReturn('/admin/user/42/roles');
        $request = Request::create('/admin/user/42/roles', 'POST', ['roles' => $submitted, '_token' => 'roles-token-42']);

        $response = $this->controller($manager, $request, $router)->roles(42, $request);

        self::assertSame('/admin/user/42/roles', $response->headers->get('Location'));
        self::assertSame(['user_roles_were_updated'], $request->getSession()->getFlashBag()->peek('success'));
    }

    /** @return iterable<string, array{mixed, list<string>}> */
    public static function submittedRoles(): iterable
    {
        yield 'valid and invalid values' => [
            ['ROLE_EDITOR', 'ROLE_UNKNOWN', 'ROLE_EDITOR', 17, null, ['ROLE_ADMIN'], 'ROLE_USER'],
            ['ROLE_EDITOR', 'ROLE_USER'],
        ];
        yield 'missing required role' => [['ROLE_ADMIN'], ['ROLE_ADMIN', 'ROLE_USER']];
        yield 'empty selection' => [[], ['ROLE_USER']];
        yield 'scalar payload' => ['ROLE_ADMIN', ['ROLE_USER']];
    }

    public function test_roles_get_uses_the_host_template_and_choices_without_persisting(): void
    {
        $user = $this->createStub(UserEntityInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($user);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects($this->never())->method('persist');
        $manager->expects($this->never())->method('flush');
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->with('host/user/roles.html.twig', [
            'user' => $user,
            'roles' => UserControllerTestRole::cases(),
        ])->willReturn('roles page');
        $request = Request::create('/admin/user/42/roles');

        $response = $this->controller($manager, $request, twig: $twig)->roles(42, $request);

        self::assertSame('roles page', $response->getContent());
    }

    #[DataProvider('invalidCsrfTokens')]
    public function test_roles_reject_invalid_csrf_without_mutating_the_user(mixed $token): void
    {
        $user = $this->createMock(UserEntityInterface::class);
        $user->expects($this->never())->method('setRoles');
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($user);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects($this->never())->method('persist');
        $manager->expects($this->never())->method('flush');
        $request = Request::create('/admin/user/42/roles', 'POST', ['roles' => ['ROLE_ADMIN'], '_token' => $token]);
        $controller = $this->controller($manager, $request);

        $this->expectException(AccessDeniedException::class);
        $controller->roles(42, $request);
    }

    public static function invalidCsrfTokens(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'invalid' => ['forged'];
        yield 'another user' => ['roles-token-43'];
        yield 'array' => [['roles-token-42']];
    }

    public function test_the_roles_template_emits_a_valid_user_scoped_csrf_token(): void
    {
        $request = Request::create('/admin/user/42/roles');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $csrfManager = new CsrfTokenManager(storage: new SessionTokenStorage($requestStack));
        $template = file_get_contents(__DIR__ . '/../../../templates/admin/user/roles.html.twig');
        self::assertIsString($template);
        $twig = new Environment(new ArrayLoader([
            'roles.html.twig' => $template,
            '@LexioAdmin/admin/flash.stream.html.twig' => '',
        ]));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (string $id): string => $csrfManager->getToken($id)->getValue()));
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/admin/user'));
        $twig->addFunction(new TwigFunction('ux_icon', static fn (): string => ''));
        $twig->addFilter(new TwigFilter('trans', static fn (string $key): string => $key));

        $html = $twig->render('roles.html.twig', ['user' => ['id' => 42, 'roles' => []], 'roles' => []]);

        self::assertSame(1, preg_match('/name="_token" value="([^"]+)"/', $html, $matches));
        $token = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        self::assertTrue($csrfManager->isTokenValid(new CsrfToken('admin.user.roles.42', $token)));
        self::assertFalse($csrfManager->isTokenValid(new CsrfToken('admin.user.roles.43', $token)));
    }

    public function test_profile_rejects_mixed_form_submissions_before_binding_or_flushing(): void
    {
        $user = new UserControllerTestUser();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(true);
        $hasher->expects($this->never())->method('hashPassword');
        $request = Request::create('/admin/user/profile', 'POST', [
            'profile' => ['firstName' => 'x'],
            'password' => ['oldPassword' => 'current', 'newPassword' => 'new-password'],
        ]);
        $controller = $this->controller($manager, $request, user: $user, passwordHasher: $hasher);

        try {
            $controller->profile($request);
            self::fail('Ambiguous profile submissions must be rejected.');
        } catch (BadRequestHttpException) {
            self::assertSame('Original', $user->firstName);
            self::assertSame('original-hash', $user->getPassword());
        }
    }

    #[DataProvider('profileSubmissions')]
    public function test_profile_processes_only_the_selected_valid_form(array $submission, string $expectedName, string $expectedPassword): void
    {
        $user = new UserControllerTestUser();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->with($user, 'current')->willReturn(true);
        $hasher->method('hashPassword')->with($user, 'new-password')->willReturn('new-hash');
        $request = Request::create('/admin/user/profile', 'POST', $submission);
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/admin/user/profile');

        $response = $this->controller($manager, $request, $router, user: $user, passwordHasher: $hasher)->profile($request);

        self::assertSame('/admin/user/profile', $response->headers->get('Location'));
        self::assertSame($expectedName, $user->firstName);
        self::assertSame($expectedPassword, $user->getPassword());
    }

    public static function profileSubmissions(): iterable
    {
        yield 'profile only' => [['profile' => ['firstName' => 'Updated']], 'Updated', 'original-hash'];
        yield 'password only' => [['password' => ['oldPassword' => 'current', 'newPassword' => 'new-password']], 'Original', 'new-hash'];
    }

    public function test_invalid_current_password_does_not_flush(): void
    {
        $user = new UserControllerTestUser();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(false);
        $hasher->expects($this->never())->method('hashPassword');
        $request = Request::create('/admin/user/profile', 'POST', ['password' => ['oldPassword' => 'wrong', 'newPassword' => 'new-password']]);
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('profile page');

        $response = $this->controller($manager, $request, twig: $twig, user: $user, passwordHasher: $hasher)->profile($request);

        self::assertSame('profile page', $response->getContent());
        self::assertSame('original-hash', $user->getPassword());
    }

    public function test_invalid_profile_does_not_flush_or_process_the_password_form(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('isPasswordValid');
        $hasher->expects($this->never())->method('hashPassword');
        $request = Request::create('/admin/user/profile', 'POST', ['profile' => ['firstName' => 'x']]);
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('invalid profile page');

        $response = $this->controller($manager, $request, twig: $twig, user: new UserControllerTestUser(), passwordHasher: $hasher)->profile($request);

        self::assertSame('invalid profile page', $response->getContent());
    }

    public function test_bulk_delete_removes_the_selected_users_and_redirects_without_a_referer(): void
    {
        $users = [$this->createStub(UserEntityInterface::class), $this->createStub(UserEntityInterface::class)];
        $bulkContext = $this->createMock(BulkContext::class);
        $bulkContext->expects($this->once())->method('getEntities')->with(UserEntityInterface::class)->willReturn($users);
        $manager = $this->createMock(EntityManagerInterface::class);
        $removed = [];
        $manager->expects($this->exactly(2))->method('remove')->willReturnCallback(static function (object $user) use (&$removed): void {
            $removed[] = $user;
        });
        $manager->expects($this->once())->method('flush');
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())->method('generate')->with('admin.user.index')->willReturn('/admin/user');
        $request = Request::create('/admin/user/bulk-delete', 'POST');

        $response = $this->controller($manager, $request, $router)->bulkDelete($bulkContext, $request);

        self::assertSame($users, $removed);
        self::assertSame('/admin/user', $response->headers->get('Location'));
    }

    private function controller(
        EntityManagerInterface $manager,
        Request $request,
        ?RouterInterface $router = null,
        ?Environment $twig = null,
        ?UserEntityInterface $user = null,
        ?UserPasswordHasherInterface $passwordHasher = null,
    ): UserController {
        $controller = new class(
            $this->createStub(UserInvitationInterface::class),
            $passwordHasher ?? $this->createStub(UserPasswordHasherInterface::class),
        ) extends UserController {
            public function getEntityFqcn(): string
            {
                return UserEntityInterface::class;
            }

            public function userEntity(): UserEntityInterface
            {
                throw new \LogicException('This test does not create a user.');
            }

            public function getUserFormType(): string
            {
                return UserControllerTestProfileType::class;
            }

            public function getPasswordFormType(): string
            {
                return UserControllerTestPasswordType::class;
            }

            public function getUserListingFilter(): BaseFilter
            {
                return new BaseFilter();
            }

            public function getRoleChoices(): array
            {
                return UserControllerTestRole::cases();
            }

            protected function getListingTemplate(): string
            {
                return 'host/user/listing.html.twig';
            }

            protected function getUpdateTemplate(): string
            {
                return 'host/user/update.html.twig';
            }

            protected function getRolesTemplate(): string
            {
                return 'host/user/roles.html.twig';
            }

            protected function getProfileTemplate(): string
            {
                return 'host/user/profile.html.twig';
            }
        };
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($manager);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $container = new Container();
        $container->set('doctrine', $registry);
        $container->set('translator', $translator);
        $container->set('router', $router ?? $this->createStub(RouterInterface::class));
        $container->set('twig', $twig ?? $this->createStub(Environment::class));
        $container->set('request_stack', $requestStack);
        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturnCallback(static fn (CsrfToken $token): bool =>
            $token->getId() === 'admin.user.roles.42' && $token->getValue() === 'roles-token-42',
        );
        $container->set('security.csrf.token_manager', $csrfManager);
        if (null !== $user) {
            $tokenStorage = new TokenStorage();
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
            $container->set('security.token_storage', $tokenStorage);
            $container->set('form.factory', Forms::createFormFactoryBuilder()
                ->addExtension(new HttpFoundationExtension())
                ->addExtension(new ValidatorExtension(Validation::createValidator()))
                ->getFormFactory());
        }
        $controller->setContainer($container);

        return $controller;
    }
}

enum UserControllerTestRole: string
{
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_EDITOR = 'ROLE_EDITOR';
    case ROLE_USER = 'ROLE_USER';
}

final class UserControllerTestProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('firstName', TextType::class, ['constraints' => [new Length(min: 3)]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UserControllerTestUser::class, 'show_blocked' => true]);
    }

    public function getBlockPrefix(): string
    {
        return 'profile';
    }
}

final class UserControllerTestPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('oldPassword', TextType::class, ['mapped' => false])
            ->add('newPassword', TextType::class, ['mapped' => false]);
    }

    public function getBlockPrefix(): string
    {
        return 'password';
    }
}

final class UserControllerTestUser implements UserEntityInterface
{
    public string $firstName = 'Original';
    private string $password = 'original-hash';

    public function getId(): int
    {
        return 42;
    }

    public function getEmail(): string
    {
        return 'test@example.com';
    }

    public function getFullName(): string
    {
        return $this->firstName;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function setRoles(array $roles): static
    {
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function eraseCredentials(): void {}

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
}
