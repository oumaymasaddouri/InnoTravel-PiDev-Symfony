<?php
// Get the Doctrine entity manager
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Entity\Itineraire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Array of Tunisia itineraries
$itineraries = [
    [
        'name' => 'Tunis City Tour',
        'dayProgram' => "Day 1: Explore Medina of Tunis\nDay 2: Visit Bardo Museum\nDay 3: Carthage Archaeological Site",
        'activity' => 'Historical tours, Cultural experiences, Traditional cuisine tasting'
    ],
    [
        'name' => 'Sidi Bou Said Escape',
        'dayProgram' => "Day 1: Explore blue and white village\nDay 2: Visit local art galleries\nDay 3: Relax at cafes overlooking the Mediterranean",
        'activity' => 'Photography, Art appreciation, Relaxation, Scenic views'
    ],
    [
        'name' => 'Djerba Island Adventure',
        'dayProgram' => "Day 1: Houmt Souk markets\nDay 2: Djerba beaches\nDay 3: Visit El Ghriba Synagogue",
        'activity' => 'Beach activities, Shopping, Cultural exploration, Water sports'
    ],
    [
        'name' => 'Sahara Desert Experience',
        'dayProgram' => "Day 1: Douz - Gateway to the Sahara\nDay 2: Camel trek and overnight in desert camp\nDay 3: Visit oasis and Berber villages",
        'activity' => 'Camel riding, Stargazing, Sand boarding, Cultural immersion'
    ],
    [
        'name' => 'Carthage and Ruins Tour',
        'dayProgram' => "Day 1: Carthage Archaeological Park\nDay 2: Antonine Baths\nDay 3: Carthage Museum and Byrsa Hill",
        'activity' => 'Archaeological exploration, Historical learning, Photography'
    ],
    [
        'name' => 'Hammamet Beach Getaway',
        'dayProgram' => "Day 1: Relax on Hammamet beaches\nDay 2: Explore Medina and Kasbah\nDay 3: Visit Yasmine Hammamet",
        'activity' => 'Beach relaxation, Swimming, Water sports, Shopping'
    ],
    [
        'name' => 'Kairouan Holy City Tour',
        'dayProgram' => "Day 1: Great Mosque of Kairouan\nDay 2: Medina exploration\nDay 3: Visit to Aghlabid Basins",
        'activity' => 'Religious sites, Cultural heritage, Traditional crafts'
    ],
    [
        'name' => 'Sousse Coastal Experience',
        'dayProgram' => "Day 1: Sousse Medina and Archaeological Museum\nDay 2: Port El Kantaoui\nDay 3: Sousse beaches",
        'activity' => 'Beach activities, Historical sites, Shopping, Dining'
    ],
    [
        'name' => 'Monastir Historical Journey',
        'dayProgram' => "Day 1: Ribat of Monastir\nDay 2: Bourguiba Mausoleum\nDay 3: Monastir beaches",
        'activity' => 'Historical sites, Cultural experiences, Beach relaxation'
    ],
    [
        'name' => 'Tabarka Coral Coast',
        'dayProgram' => "Day 1: Tabarka beach and coral reefs\nDay 2: The Needles rock formations\nDay 3: Tabarka Jazz Festival (seasonal)",
        'activity' => 'Diving, Snorkeling, Music appreciation, Nature photography'
    ],
    [
        'name' => 'Matmata Star Wars Tour',
        'dayProgram' => "Day 1: Visit troglodyte dwellings\nDay 2: Star Wars film locations\nDay 3: Berber cultural experience",
        'activity' => 'Film tourism, Cultural immersion, Photography, Unique accommodations'
    ],
    [
        'name' => 'Tozeur Oasis Adventure',
        'dayProgram' => "Day 1: Explore palm groves\nDay 2: Chebika and Tamerza mountain oases\nDay 3: Ride the Red Lizard Train",
        'activity' => 'Nature exploration, Desert trekking, Photography, Traditional agriculture'
    ],
    [
        'name' => 'El Jem Amphitheater Day',
        'dayProgram' => "Day 1: El Jem Amphitheater\nDay 2: Archaeological Museum\nDay 3: Surrounding Roman ruins",
        'activity' => 'Historical exploration, Archaeological sites, Photography'
    ],
    [
        'name' => 'Cap Bon Peninsula Tour',
        'dayProgram' => "Day 1: Nabeul pottery town\nDay 2: Kelibia beaches and fort\nDay 3: Korbous hot springs",
        'activity' => 'Artisan crafts, Beach activities, Thermal spa relaxation'
    ],
    [
        'name' => 'Bizerte Northern Coast',
        'dayProgram' => "Day 1: Old Port and Kasbah\nDay 2: Beaches of Bizerte\nDay 3: Ichkeul National Park",
        'activity' => 'Coastal exploration, Bird watching, Historical sites, Seafood dining'
    ]
];

// Add each itinerary to the database
foreach ($itineraries as $itineraryData) {
    $itinerary = new Itineraire();
    $itinerary->setName($itineraryData['name']);
    $itinerary->setDayProgram($itineraryData['dayProgram']);
    $itinerary->setActivity($itineraryData['activity']);
    
    $entityManager->persist($itinerary);
    echo "Added itinerary: " . $itineraryData['name'] . "\n";
}

// Flush all changes to the database
$entityManager->flush();
echo "All 15 Tunisia itineraries have been added to the database.\n";
