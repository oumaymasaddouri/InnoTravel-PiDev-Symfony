<?php

namespace App\Command;

use App\DataFixtures\TunisianEventsFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-tunisian-events',
    description: 'Load Tunisian organizers and events fixtures',
)]
class LoadTunisianEventsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TunisianEventsFixtures $fixtures;

    public function __construct(EntityManagerInterface $entityManager, TunisianEventsFixtures $fixtures)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->fixtures = $fixtures;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Loading Tunisian Events Fixtures');
        
        try {
            $io->section('Adding organizers and events...');
            $this->fixtures->load($this->entityManager);
            
            $io->success('Successfully loaded Tunisian organizers and events!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
