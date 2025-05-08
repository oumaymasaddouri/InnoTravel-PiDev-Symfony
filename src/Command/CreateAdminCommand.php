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
    name: 'app:create-admin',
    description: 'Creates an admin user',
)]
class CreateAdminCommand extends Command
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

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $adminEmail]);

        if ($existingAdmin) {
            $io->warning('Admin user already exists!');
            return Command::SUCCESS;
        }

        // Create new admin user
        $admin = new User();
        $admin->setEmail($adminEmail);
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN']);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $adminPassword
        );
        $admin->setPassword($hashedPassword);

        // Set required fields
        $admin->setGender('Other');
        $admin->setDateOfBirth(new \DateTime('1990-01-01'));
        $admin->setPhoneNumber('123456789');
        $admin->setCountry('Tunisia');

        // Save to database
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');

        return Command::SUCCESS;
    }
}
