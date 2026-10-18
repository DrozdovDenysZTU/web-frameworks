<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:create-user', description: 'Creates a user with the given role')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('role', InputArgument::OPTIONAL, implode('|', User::ROLES), User::ROLE_ADMIN)
            ->addArgument('name', InputArgument::OPTIONAL, '', 'Administrator');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (mb_strlen($input->getArgument('password')) < 8) {
            $io->error('password: Password must be at least 8 characters.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($input->getArgument('email'))
            ->setName($input->getArgument('name'))
            ->setRole($input->getArgument('role'));
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->getArgument('password')));

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error(sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User %s created with role %s.', $user->getEmail(), $user->getRole()));

        return Command::SUCCESS;
    }
}
