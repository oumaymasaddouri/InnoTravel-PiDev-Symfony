<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Organizer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TunisianEventsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create Organizers
        $organizers = $this->createOrganizers($manager);
        
        // Create Events
        $this->createEvents($manager, $organizers);
        
        $manager->flush();
    }
    
    private function createOrganizers(ObjectManager $manager): array
    {
        $organizersData = [
            [
                'name' => 'Tunisia Tourism Board',
                'description' => 'The official tourism board of Tunisia, dedicated to promoting the country\'s rich cultural heritage, beautiful landscapes, and diverse experiences. We organize various cultural events, festivals, and tours to showcase the best of Tunisia to visitors from around the world.',
                'email' => 'contact@tunisiatourism.tn',
                'phone' => '+216 71 234 567',
                'website' => 'https://www.tunisiatourism.info',
                'isVerified' => true
            ],
            [
                'name' => 'Carthage Events',
                'description' => 'Carthage Events is a premier event management company specializing in cultural and historical experiences in Tunisia. Based in Tunis, we organize events that highlight Tunisia\'s rich Carthaginian, Roman, and Arab heritage, offering unique experiences at historical sites across the country.',
                'email' => 'info@carthageevents.tn',
                'phone' => '+216 71 987 654',
                'website' => 'https://www.carthageevents.tn',
                'isVerified' => true
            ],
            [
                'name' => 'Sahara Adventures',
                'description' => 'Sahara Adventures specializes in desert experiences and southern Tunisia exploration. We organize desert treks, camel caravans, star-gazing nights, and cultural exchanges with local Berber communities. Our team of experienced guides ensures safe and authentic desert adventures.',
                'email' => 'explore@saharaadventures.tn',
                'phone' => '+216 75 123 456',
                'website' => 'https://www.saharaadventures.tn',
                'isVerified' => true
            ],
            [
                'name' => 'Mediterranean Festivals',
                'description' => 'Mediterranean Festivals is dedicated to celebrating Tunisia\'s coastal culture and maritime heritage. We organize beach festivals, seafood events, sailing competitions, and cultural exchanges with other Mediterranean countries, highlighting Tunisia\'s position as a Mediterranean crossroads.',
                'email' => 'festivals@medfest.tn',
                'phone' => '+216 73 456 789',
                'website' => 'https://www.medfest.tn',
                'isVerified' => true
            ],
            [
                'name' => 'Tunisian Artisans Collective',
                'description' => 'The Tunisian Artisans Collective promotes traditional Tunisian crafts and artisanal skills. We organize workshops, exhibitions, and markets featuring pottery, carpet weaving, metalwork, and other traditional crafts, supporting local artisans and preserving Tunisia\'s rich craft heritage.',
                'email' => 'artisans@tunisiancrafts.org',
                'phone' => '+216 71 345 678',
                'website' => 'https://www.tunisiancrafts.org',
                'isVerified' => true
            ],
            [
                'name' => 'Dougga Historical Society',
                'description' => 'The Dougga Historical Society is dedicated to preserving and promoting the UNESCO World Heritage site of Dougga. We organize educational tours, historical reenactments, and archaeological workshops at one of North Africa\'s best-preserved Roman cities.',
                'email' => 'info@douggahistory.org',
                'phone' => '+216 78 654 321',
                'website' => 'https://www.douggahistory.org',
                'isVerified' => true
            ],
            [
                'name' => 'Sidi Bou Said Cultural Association',
                'description' => 'The Sidi Bou Said Cultural Association promotes the artistic heritage of Tunisia\'s famous blue and white village. We organize art exhibitions, music performances, and cultural events in this picturesque setting, supporting local artists and musicians.',
                'email' => 'culture@sidibousaid.org',
                'phone' => '+216 71 765 432',
                'website' => 'https://www.sidibousaid.org',
                'isVerified' => true
            ]
        ];
        
        $organizerEntities = [];
        
        foreach ($organizersData as $organizerData) {
            $organizer = new Organizer();
            $organizer->setName($organizerData['name']);
            $organizer->setDescription($organizerData['description']);
            $organizer->setEmail($organizerData['email']);
            $organizer->setPhone($organizerData['phone']);
            $organizer->setWebsite($organizerData['website']);
            $organizer->setIsVerified($organizerData['isVerified']);
            $organizer->setUpdatedAt(new \DateTime());
            
            $manager->persist($organizer);
            $organizerEntities[$organizerData['name']] = $organizer;
        }
        
        return $organizerEntities;
    }
    
    private function createEvents(ObjectManager $manager, array $organizers): void
    {
        $eventsData = [
            [
                'name' => 'Carthage International Festival',
                'description' => 'The Carthage International Festival is one of Tunisia\'s premier cultural events, held annually in the ancient Roman amphitheater of Carthage. The festival features performances by international and Tunisian musicians, dancers, and theater groups against the backdrop of this magnificent UNESCO World Heritage site. Experience world-class performances under the stars in a venue that has hosted entertainment for over 2,000 years.',
                'location' => 'Roman Amphitheater of Carthage, Tunis',
                'startDate' => new \DateTime('+2 months'),
                'endDate' => new \DateTime('+2 months +8 hours'),
                'capacity' => 500,
                'price' => 120.00,
                'category' => 'Cultural Festival',
                'organizer' => 'Carthage Events',
                'isActive' => true
            ],
            [
                'name' => 'Sahara Desert Expedition',
                'description' => 'Embark on an unforgettable journey into the heart of the Tunisian Sahara. This expedition takes you through stunning desert landscapes, from the salt flats of Chott el Jerid to the sand dunes of the Grand Erg Oriental. Camp under the stars, enjoy traditional Berber cuisine, and experience the magic of the desert. The expedition includes camel trekking, 4x4 adventures, and visits to desert oases and traditional villages.',
                'location' => 'Douz (meeting point), Southern Tunisia',
                'startDate' => new \DateTime('+1 month'),
                'endDate' => new \DateTime('+1 month +10 hours'),
                'capacity' => 30,
                'price' => 350.00,
                'category' => 'Adventure',
                'organizer' => 'Sahara Adventures',
                'isActive' => true
            ],
            [
                'name' => 'Medina Craft Market',
                'description' => 'Discover the rich artisanal heritage of Tunisia at this special craft market held in the heart of Tunis\' historic medina. Browse stalls featuring traditional Tunisian crafts including ceramics, carpets, leather goods, filigree silverwork, and more. Meet the artisans, learn about their techniques, and find unique handcrafted souvenirs. The market also features demonstrations of traditional craft techniques and workshops where visitors can try their hand at Tunisian crafts.',
                'location' => 'Medina of Tunis',
                'startDate' => new \DateTime('+2 weeks'),
                'endDate' => new \DateTime('+2 weeks +6 hours'),
                'capacity' => 200,
                'price' => 0.00,
                'category' => 'Cultural',
                'organizer' => 'Tunisian Artisans Collective',
                'isActive' => true
            ],
            [
                'name' => 'Dougga Archaeological Tour',
                'description' => 'Join expert archaeologists for a special guided tour of Dougga, one of North Africa\'s best-preserved Roman cities and a UNESCO World Heritage site. Explore the Capitol, the theaters, the temples, and the unique Libyo-Punic Mausoleum. This in-depth tour offers insights into daily life in ancient Dougga and the latest archaeological discoveries. The event includes a picnic lunch featuring local specialties and a lecture on the site\'s historical significance.',
                'location' => 'Dougga Archaeological Site, Béja Governorate',
                'startDate' => new \DateTime('+3 weeks'),
                'endDate' => new \DateTime('+3 weeks +5 hours'),
                'capacity' => 40,
                'price' => 85.00,
                'category' => 'Educational',
                'organizer' => 'Dougga Historical Society',
                'isActive' => true
            ],
            [
                'name' => 'Sidi Bou Said Jazz Festival',
                'description' => 'Experience the magic of jazz in the picturesque setting of Sidi Bou Said, Tunisia\'s famous blue and white village overlooking the Mediterranean. This intimate festival features performances by international and Tunisian jazz musicians in the village\'s historic Ennejma Ezzahra palace and in outdoor venues throughout the village. Enjoy world-class music while taking in the stunning views and unique atmosphere of this artistic haven.',
                'location' => 'Sidi Bou Said, Tunis',
                'startDate' => new \DateTime('+1 month +2 weeks'),
                'endDate' => new \DateTime('+1 month +2 weeks +7 hours'),
                'capacity' => 150,
                'price' => 95.00,
                'category' => 'Music',
                'organizer' => 'Sidi Bou Said Cultural Association',
                'isActive' => true
            ],
            [
                'name' => 'Coastal Cuisine Festival',
                'description' => 'Celebrate Tunisia\'s rich Mediterranean culinary traditions at this food festival held in the coastal town of Hammamet. Sample a wide variety of seafood dishes, traditional Tunisian specialties, and fusion cuisine prepared by top chefs from across the country. The festival includes cooking demonstrations, tasting sessions, and a market selling local produce, spices, and culinary products. Enjoy your meal with views of Hammamet\'s beautiful beaches and historic fortress.',
                'location' => 'Hammamet Beach Promenade',
                'startDate' => new \DateTime('+2 months +1 week'),
                'endDate' => new \DateTime('+2 months +1 week +8 hours'),
                'capacity' => 300,
                'price' => 65.00,
                'category' => 'Food & Drink',
                'organizer' => 'Mediterranean Festivals',
                'isActive' => true
            ],
            [
                'name' => 'Berber Cultural Festival',
                'description' => 'Immerse yourself in the rich cultural heritage of Tunisia\'s Berber communities at this festival held in the mountain village of Takrouna. Experience traditional Berber music, dance, and storytelling, and explore exhibits of Berber crafts, clothing, and artifacts. The festival includes demonstrations of traditional Berber cooking, weaving, and pottery-making, as well as guided tours of the historic village. Connect with Tunisia\'s indigenous culture in an authentic setting.',
                'location' => 'Takrouna Village, Sousse Governorate',
                'startDate' => new \DateTime('+3 months'),
                'endDate' => new \DateTime('+3 months +9 hours'),
                'capacity' => 120,
                'price' => 45.00,
                'category' => 'Cultural',
                'organizer' => 'Tunisia Tourism Board',
                'isActive' => true
            ],
            [
                'name' => 'Star Wars Tour: Tunisia\'s Film Locations',
                'description' => 'Visit the iconic Tunisian locations that served as the backdrop for the planet Tatooine in the Star Wars films. This special tour takes you to sites including the Hotel Sidi Driss in Matmata (Luke Skywalker\'s home), the Mos Espa set in the Ong Jemel desert, and other filming locations. Learn about how these Tunisian landscapes inspired George Lucas and how the films were made. The tour includes transportation, accommodation, meals, and a Star Wars-themed evening event.',
                'location' => 'Tozeur (meeting point), Southern Tunisia',
                'startDate' => new \DateTime('+1 month +3 weeks'),
                'endDate' => new \DateTime('+1 month +3 weeks +8 hours'),
                'capacity' => 25,
                'price' => 280.00,
                'category' => 'Special Interest',
                'organizer' => 'Sahara Adventures',
                'isActive' => true
            ],
            [
                'name' => 'Tunisian Wine Tasting Tour',
                'description' => 'Discover Tunisia\'s little-known but excellent wine tradition on this tour of the Cap Bon peninsula\'s vineyards. Visit three different wineries, learn about the unique conditions that make this region ideal for viticulture, and sample a variety of Tunisian wines including local varieties like Muscat of Alexandria and Carignan. The tour includes transportation from Tunis, a gourmet lunch featuring local specialties, and expert guidance from a wine specialist.',
                'location' => 'Cap Bon Peninsula, departing from Tunis',
                'startDate' => new \DateTime('+2 months +2 weeks'),
                'endDate' => new \DateTime('+2 months +2 weeks +7 hours'),
                'capacity' => 20,
                'price' => 175.00,
                'category' => 'Food & Drink',
                'organizer' => 'Mediterranean Festivals',
                'isActive' => true
            ],
            [
                'name' => 'Traditional Pottery Workshop',
                'description' => 'Learn the art of traditional Tunisian pottery in this hands-on workshop held in the pottery center of Sejnane, known for its unique hand-built ceramics made by Berber women using techniques that date back thousands of years. Under the guidance of master potters, create your own piece using local clay and traditional methods. The workshop includes all materials, lunch featuring local specialties, and transportation of your finished piece back to your hotel after firing.',
                'location' => 'Sejnane, Bizerte Governorate',
                'startDate' => new \DateTime('+1 month +1 week'),
                'endDate' => new \DateTime('+1 month +1 week +6 hours'),
                'capacity' => 15,
                'price' => 120.00,
                'category' => 'Workshop',
                'organizer' => 'Tunisian Artisans Collective',
                'isActive' => true
            ],
            [
                'name' => 'El Jem Classical Concert',
                'description' => 'Experience the magic of classical music in the magnificent setting of El Jem\'s Roman amphitheater, one of the largest Roman coliseums in the world. This special concert features the Tunisian Symphony Orchestra performing works by Mozart, Beethoven, and Rossini, as well as pieces inspired by traditional Tunisian music. The acoustics and atmosphere of this 3rd-century monument create an unforgettable musical experience under the stars.',
                'location' => 'El Jem Amphitheater, El Jem',
                'startDate' => new \DateTime('+2 months +3 weeks'),
                'endDate' => new \DateTime('+2 months +3 weeks +4 hours'),
                'capacity' => 400,
                'price' => 110.00,
                'category' => 'Music',
                'organizer' => 'Carthage Events',
                'isActive' => true
            ],
            [
                'name' => 'Tunisian Olive Harvest Experience',
                'description' => 'Participate in the traditional olive harvest in Tunisia\'s olive-growing heartland. Join local farmers in the groves to pick olives using traditional methods, visit an olive oil press to see how the oil is extracted, and enjoy a farm-to-table lunch featuring fresh olive oil. Learn about the importance of olive cultivation in Tunisian culture and economy, and take home a bottle of the newly-pressed oil. This authentic agricultural experience connects you with Tunisia\'s land and people.',
                'location' => 'Zaghouan Region',
                'startDate' => new \DateTime('+3 months +1 week'),
                'endDate' => new \DateTime('+3 months +1 week +8 hours'),
                'capacity' => 25,
                'price' => 90.00,
                'category' => 'Agricultural',
                'organizer' => 'Tunisia Tourism Board',
                'isActive' => true
            ],
            [
                'name' => 'Kairouan Sacred Music Festival',
                'description' => 'Experience the spiritual sounds of sacred music in Kairouan, Tunisia\'s holy city and a UNESCO World Heritage site. This festival brings together performers of sacred music traditions from across North Africa, the Middle East, and beyond. Performances take place in historic venues including the Great Mosque courtyard and traditional madrasas. The festival celebrates the diversity of religious musical expressions and Kairouan\'s importance as a center of Islamic culture.',
                'location' => 'Various venues in Kairouan',
                'startDate' => new \DateTime('+2 months +1 week'),
                'endDate' => new \DateTime('+2 months +1 week +6 hours'),
                'capacity' => 200,
                'price' => 75.00,
                'category' => 'Music',
                'organizer' => 'Tunisia Tourism Board',
                'isActive' => true
            ],
            [
                'name' => 'Tunisian Mosaic Workshop',
                'description' => 'Learn the art of traditional Tunisian mosaic-making in this hands-on workshop led by master craftsmen. Create your own mosaic piece using techniques that date back to the Roman era, when Tunisia (then part of Carthage) was renowned for its exceptional mosaics. The workshop takes place in El Jem, home to an outstanding museum of Roman mosaics that you\'ll visit for inspiration. All materials are provided, and your finished piece will be yours to take home.',
                'location' => 'El Jem, Mahdia Governorate',
                'startDate' => new \DateTime('+1 month +2 weeks'),
                'endDate' => new \DateTime('+1 month +2 weeks +5 hours'),
                'capacity' => 20,
                'price' => 95.00,
                'category' => 'Workshop',
                'organizer' => 'Tunisian Artisans Collective',
                'isActive' => true
            ],
            [
                'name' => 'Tabarka Jazz Festival',
                'description' => 'The Tabarka Jazz Festival is one of North Africa\'s premier jazz events, held in the beautiful coastal town of Tabarka. Enjoy performances by international and Tunisian jazz musicians in venues including the Byzantine fort and beachfront stages. The festival\'s unique setting, combining Mediterranean beaches, coral reefs, and nearby mountain forests, makes it a perfect cultural vacation. Beyond the music, enjoy Tabarka\'s famous seafood and relaxed atmosphere.',
                'location' => 'Tabarka, Jendouba Governorate',
                'startDate' => new \DateTime('+3 months +2 weeks'),
                'endDate' => new \DateTime('+3 months +2 weeks +8 hours'),
                'capacity' => 350,
                'price' => 130.00,
                'category' => 'Music',
                'organizer' => 'Mediterranean Festivals',
                'isActive' => true
            ]
        ];
        
        foreach ($eventsData as $eventData) {
            $event = new Event();
            $event->setName($eventData['name']);
            $event->setDescription($eventData['description']);
            $event->setLocation($eventData['location']);
            $event->setStartDate($eventData['startDate']);
            $event->setEndDate($eventData['endDate']);
            $event->setCapacity($eventData['capacity']);
            $event->setPrice($eventData['price']);
            $event->setCategory($eventData['category']);
            $event->setOrganizer($organizers[$eventData['organizer']]);
            $event->setIsActive($eventData['isActive']);
            $event->setAvailableSpots($eventData['capacity']);
            $event->setUpdatedAt(new \DateTime());
            
            $manager->persist($event);
        }
    }
}
