<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user account',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('name', InputArgument::REQUIRED, 'Display name')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password (will prompt if not provided)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');

        if (!$password) {
            $password = $io->askHidden('Password');
            if (!$password) {
                $io->error('Password cannot be empty.');
                return Command::FAILURE;
            }
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setDisplayName($name);
        $user->setIsVerified(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        if ($input->getOption('admin')) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" (%s) created successfully.', $name, $email));

        return Command::SUCCESS;
    }
}
