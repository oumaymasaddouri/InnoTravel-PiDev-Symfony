<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:update-admin-password',
    description: 'Updates the admin password',
)]
class UpdateAdminPasswordCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get admin email from environment variable
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@innotravel.tn';
        $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'change_this_password';

        // Find admin user
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $adminEmail]);

        if (!$admin) {
            $io->error('Admin user not found!');
            return Command::FAILURE;
        }

        // Update password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $adminPassword
        );
        $admin->setPassword($hashedPassword);

        // Make sure admin has ROLE_ADMIN
        $roles = $admin->getRoles();
        if (!in_array('ROLE_ADMIN', $roles)) {
            $admin->setRoles(['ROLE_ADMIN']);
        }

        // Save to database
        $this->entityManager->flush();

        $io->success('Admin password updated successfully!');

        return Command::SUCCESS;
    }
}
