<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateUsersCommand extends Command
{
    protected static $defaultName = 'app:update-users';
    protected static $defaultDescription = 'Update users with realistic names, emails, countries, and phone numbers';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating users with realistic information');

        // Get all users
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        if (empty($users)) {
            $io->error('No users found in the database.');
            return Command::FAILURE;
        }
        
        $io->note(sprintf('Found %d users to update', count($users)));
        
        // User data with realistic information
        $userData = [
            [
                'firstName' => 'Michael',
                'lastName' => 'Johnson',
                'email' => 'michael.johnson@gmail.com',
                'country' => 'United States',
                'phoneNumber' => '+1 212 555 7890'
            ],
            [
                'firstName' => 'Emma',
                'lastName' => 'Williams',
                'email' => 'emma.williams@outlook.com',
                'country' => 'Canada',
                'phoneNumber' => '+1 416 555 3456'
            ],
            [
                'firstName' => 'James',
                'lastName' => 'Wilson',
                'email' => 'james.wilson@gmail.com',
                'country' => 'United Kingdom',
                'phoneNumber' => '+44 20 7946 0958'
            ],
            [
                'firstName' => 'Olivia',
                'lastName' => 'Brown',
                'email' => 'olivia.brown@yahoo.com',
                'country' => 'Australia',
                'phoneNumber' => '+61 2 8765 4321'
            ],
            [
                'firstName' => 'Alexander',
                'lastName' => 'Petrov',
                'email' => 'alexander.petrov@gmail.com',
                'country' => 'Russia',
                'phoneNumber' => '+7 495 123 4567'
            ],
            [
                'firstName' => 'Sophie',
                'lastName' => 'Martin',
                'email' => 'sophie.martin@outlook.fr',
                'country' => 'France',
                'phoneNumber' => '+33 1 23 45 67 89'
            ],
            [
                'firstName' => 'Lukas',
                'lastName' => 'Müller',
                'email' => 'lukas.mueller@gmail.com',
                'country' => 'Germany',
                'phoneNumber' => '+49 30 12345678'
            ],
            [
                'firstName' => 'Hiroshi',
                'lastName' => 'Tanaka',
                'email' => 'hiroshi.tanaka@gmail.com',
                'country' => 'Japan',
                'phoneNumber' => '+81 3 1234 5678'
            ],
            [
                'firstName' => 'Carlos',
                'lastName' => 'Rodriguez',
                'email' => 'carlos.rodriguez@gmail.com',
                'country' => 'Spain',
                'phoneNumber' => '+34 91 123 4567'
            ],
            [
                'firstName' => 'Sofia',
                'lastName' => 'Rossi',
                'email' => 'sofia.rossi@gmail.com',
                'country' => 'Italy',
                'phoneNumber' => '+39 06 1234 5678'
            ],
            [
                'firstName' => 'Wei',
                'lastName' => 'Chen',
                'email' => 'wei.chen@gmail.com',
                'country' => 'China',
                'phoneNumber' => '+86 10 1234 5678'
            ],
            [
                'firstName' => 'Maria',
                'lastName' => 'Silva',
                'email' => 'maria.silva@gmail.com',
                'country' => 'Brazil',
                'phoneNumber' => '+55 11 91234 5678'
            ],
            [
                'firstName' => 'Seo-Yun',
                'lastName' => 'Kim',
                'email' => 'seoyun.kim@gmail.com',
                'country' => 'South Korea',
                'phoneNumber' => '+82 2 1234 5678'
            ],
            [
                'firstName' => 'Priya',
                'lastName' => 'Patel',
                'email' => 'priya.patel@gmail.com',
                'country' => 'India',
                'phoneNumber' => '+91 22 1234 5678'
            ],
            [
                'firstName' => 'Isabella',
                'lastName' => 'Martinez',
                'email' => 'isabella.martinez@gmail.com',
                'country' => 'Mexico',
                'phoneNumber' => '+52 55 1234 5678'
            ],
            [
                'firstName' => 'Ahmed',
                'lastName' => 'Al-Farsi',
                'email' => 'ahmed.alfarsi@gmail.com',
                'country' => 'United Arab Emirates',
                'phoneNumber' => '+971 4 123 4567'
            ],
            [
                'firstName' => 'Liam',
                'lastName' => 'O\'Connor',
                'email' => 'liam.oconnor@gmail.com',
                'country' => 'Ireland',
                'phoneNumber' => '+353 1 234 5678'
            ],
            [
                'firstName' => 'Astrid',
                'lastName' => 'Johansson',
                'email' => 'astrid.johansson@outlook.com',
                'country' => 'Sweden',
                'phoneNumber' => '+46 8 123 456 78'
            ],
            [
                'firstName' => 'Mateo',
                'lastName' => 'Fernandez',
                'email' => 'mateo.fernandez@gmail.com',
                'country' => 'Argentina',
                'phoneNumber' => '+54 11 1234 5678'
            ],
            [
                'firstName' => 'Ava',
                'lastName' => 'Thompson',
                'email' => 'ava.thompson@yahoo.com',
                'country' => 'New Zealand',
                'phoneNumber' => '+64 9 123 4567'
            ]
        ];
        
        $progressBar = $io->createProgressBar(count($users));
        $progressBar->start();
        
        // Update each user with data from our array
        foreach ($users as $index => $user) {
            // Use modulo to cycle through our user data if we have more users than data entries
            $dataIndex = $index % count($userData);
            $data = $userData[$dataIndex];
            
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);
            
            // Check if these methods exist before calling them
            if (method_exists($user, 'setCountry')) {
                $user->setCountry($data['country']);
            }
            
            if (method_exists($user, 'setPhoneNumber')) {
                $user->setPhoneNumber($data['phoneNumber']);
            }
            
            $this->entityManager->persist($user);
            $progressBar->advance();
        }
        
        $this->entityManager->flush();
        
        $progressBar->finish();
        $io->newLine(2);
        $io->success('Successfully updated all users with realistic information!');
        
        return Command::SUCCESS;
    }
}
