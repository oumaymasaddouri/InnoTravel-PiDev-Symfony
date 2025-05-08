<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/config/bootstrap.php';

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Reaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

// Create the Kernel
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Get the entity manager
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Get a user to associate with the posts
$users = $entityManager->getRepository(User::class)->findAll();

if (empty($users)) {
    echo "No users found in the database. Please create at least one user first.\n";
    exit(1);
}

// Use the first user as the author
$author = $users[0];

// Tunisia-related post data
$tunisiaPosts = [
    [
        'title' => 'The Beauty of Sidi Bou Said',
        'content' => 'Sidi Bou Said is a picturesque blue and white village located on top of a steep cliff, overlooking the Mediterranean Sea. It\'s known for its extensive use of blue and white colors throughout the town, cobblestone streets, and stunning views. The village has inspired many artists and writers over the years, including Paul Klee and August Macke.',
        'location' => 'Sidi Bou Said, Tunisia',
        'email' => 'tourism@sidibousaid.tn',
        'num' => '+216 71 234 567'
    ],
    [
        'title' => 'Exploring the Ancient Ruins of Carthage',
        'content' => 'Carthage was once one of the most powerful cities in the ancient world. Today, visitors can explore the archaeological site which includes the Antonine Baths, the Carthage Museum, and the Tophet (a sacred burial ground). The ruins offer a fascinating glimpse into Tunisia\'s rich history and the legacy of the Carthaginian civilization.',
        'location' => 'Carthage, Tunisia',
        'email' => 'info@carthage-museum.tn',
        'num' => '+216 71 345 678'
    ],
    [
        'title' => 'The Magnificent El Jem Amphitheatre',
        'content' => 'El Jem Amphitheatre is one of the most impressive Roman remains in Africa, and it\'s the third largest amphitheater in the world. Built in the 3rd century AD, it could seat up to 35,000 spectators and was used for gladiatorial contests and other public spectacles. Today, it hosts the annual International Festival of Symphonic Music.',
        'location' => 'El Jem, Tunisia',
        'email' => 'contact@eljem-festival.tn',
        'num' => '+216 73 456 789'
    ],
    [
        'title' => 'Tunisian Cuisine: A Culinary Journey',
        'content' => 'Tunisian cuisine is a blend of Mediterranean and North African flavors. Popular dishes include couscous, brik (a thin pastry filled with egg and tuna), and tajine (a type of stew). The food is known for its spiciness, with harissa (a hot chili paste) being a staple condiment. Don\'t miss trying the traditional mint tea with pine nuts!',
        'location' => 'Tunis, Tunisia',
        'email' => 'food@tunisia-tourism.tn',
        'num' => '+216 71 567 890'
    ],
    [
        'title' => 'The Sahara Desert Experience in Tunisia',
        'content' => 'The Sahara Desert covers a significant portion of southern Tunisia. Visitors can experience camel treks, overnight stays in desert camps, and stunning sunrises and sunsets over the dunes. Douz, known as the "Gateway to the Sahara," is a popular starting point for desert excursions.',
        'location' => 'Douz, Tunisia',
        'email' => 'sahara@tunisia-tours.tn',
        'num' => '+216 75 678 901'
    ],
    [
        'title' => 'The Medina of Tunis: A UNESCO World Heritage Site',
        'content' => 'The Medina of Tunis, founded in the 7th century, is one of the first Arab-Muslim towns in the Maghreb. With its narrow alleys, colorful souks, and historic monuments like the Zitouna Mosque, it offers a journey back in time. The medina is home to over 700 monuments, including palaces, mosques, and fountains.',
        'location' => 'Tunis, Tunisia',
        'email' => 'medina@tunis-tourism.tn',
        'num' => '+216 71 789 012'
    ],
    [
        'title' => 'Djerba: The Island of Dreams',
        'content' => 'Djerba, known as the "Island of Dreams," is famous for its beautiful beaches, whitewashed houses, and peaceful atmosphere. It\'s home to one of the oldest Jewish communities in the world, with the El Ghriba Synagogue being a significant pilgrimage site. The island also offers excellent opportunities for water sports and relaxation.',
        'location' => 'Djerba, Tunisia',
        'email' => 'info@djerba-tourism.tn',
        'num' => '+216 75 890 123'
    ],
    [
        'title' => 'Kairouan: The Holy City',
        'content' => 'Kairouan is considered the fourth holiest city in Islam and is home to the Great Mosque of Kairouan, one of the most important mosques in the Muslim world. The city\'s medina is a UNESCO World Heritage site, known for its distinctive architecture and traditional carpet making. Visitors can also explore the Aghlabid Basins, an ancient water distribution system.',
        'location' => 'Kairouan, Tunisia',
        'email' => 'visit@kairouan-city.tn',
        'num' => '+216 77 901 234'
    ],
    [
        'title' => 'Tunisian Traditional Crafts and Souvenirs',
        'content' => 'Tunisia has a rich tradition of handicrafts, including pottery, carpet weaving, and metalwork. The souks (markets) in cities like Tunis, Sousse, and Sfax offer a wide variety of handmade items, from colorful ceramics to intricate jewelry. Bargaining is expected and part of the shopping experience!',
        'location' => 'Sousse, Tunisia',
        'email' => 'crafts@tunisia-artisans.tn',
        'num' => '+216 73 012 345'
    ],
    [
        'title' => 'Festivals and Celebrations in Tunisia',
        'content' => 'Tunisia hosts numerous cultural festivals throughout the year. The International Festival of Carthage features music, dance, and theater performances in the ancient amphitheater. The Sahara Festival in Douz celebrates Bedouin culture with camel races and traditional music. During Ramadan, cities come alive with special evening celebrations and festive meals.',
        'location' => 'Various locations, Tunisia',
        'email' => 'events@tunisia-festivals.tn',
        'num' => '+216 71 123 456'
    ]
];

// Comments for each post
$commentTexts = [
    [
        'I visited here last summer and it was absolutely breathtaking! The blue and white buildings against the Mediterranean backdrop are stunning.',
        'Does anyone know the best time of year to visit? I\'m planning a trip and want to avoid the crowds.',
        'The local cafes serve amazing mint tea. Don\'t miss trying it while enjoying the view!'
    ],
    [
        'The history of Carthage is fascinating. I spent a whole day exploring the ruins and still didn\'t see everything.',
        'The Antonine Baths are particularly impressive. Make sure to bring water and sun protection as there\'s not much shade.',
        'I recommend hiring a guide to really understand the historical significance of the site.'
    ],
    [
        'El Jem is truly magnificent! It\'s less crowded than the Colosseum in Rome but just as impressive.',
        'I attended the Symphony Festival here and the acoustics were amazing. Such a unique experience!',
        'The nearby museum has some beautiful mosaics that shouldn\'t be missed.'
    ],
    [
        'Tunisian food is so flavorful! I took a cooking class in Tunis and learned to make brik - it was delicious!',
        'The spice markets are a feast for the senses. I brought back some harissa and use it in everything now.',
        'Don\'t miss trying the local date pastries - they\'re sweet and perfect with mint tea.'
    ],
    [
        'Spending a night in the Sahara was one of the most magical experiences of my life. The stars were incredible!',
        'The camel trek was more comfortable than I expected. Definitely worth doing for the authentic experience.',
        'Bring warm clothes if you\'re staying overnight - the desert gets surprisingly cold after sunset.'
    ],
    [
        'Getting lost in the Medina is part of the experience! I discovered so many hidden gems just wandering around.',
        'The craftsmen in the souks are incredibly skilled. I watched a metalworker create intricate designs by hand.',
        'The Zitouna Mosque is beautiful and peaceful. A nice respite from the busy market streets.'
    ],
    [
        'Djerba has the most beautiful beaches I\'ve ever seen. The water is crystal clear and perfect for swimming.',
        'I rented a bicycle and explored the island - it\'s a great way to see the traditional villages and landscapes.',
        'The sunset views from the northern tip of the island are absolutely spectacular!'
    ],
    [
        'The Great Mosque of Kairouan is awe-inspiring. The architecture and history make it a must-visit site.',
        'I was fascinated by the traditional carpet workshops. The patterns and colors are so distinctive.',
        'The local specialty is a sweet called makroudh - absolutely delicious and worth trying!'
    ],
    [
        'I bought a beautiful hand-painted ceramic plate that now has pride of place in my home. The craftsmanship is excellent.',
        'Bargaining in the souks is expected but keep it friendly. I had some great conversations with the shopkeepers.',
        'The leather goods from Tunisia are of excellent quality. I got a beautiful bag at a fraction of what it would cost at home.'
    ],
    [
        'I was lucky enough to attend the Festival of Carthage last year. The performances in that historic setting were unforgettable.',
        'Experiencing Ramadan in Tunisia was special - the evening atmosphere was so festive and welcoming.',
        'The traditional music and dance performances are so energetic and colorful. Definitely try to catch a show!'
    ]
];

// Reaction types
$reactionTypes = [
    ['typeIndex' => 1, 'emoji' => '👍'], // Like
    ['typeIndex' => 2, 'emoji' => '❤️'], // Love
    ['typeIndex' => 3, 'emoji' => '😂'], // Haha
    ['typeIndex' => 4, 'emoji' => '😮'], // Wow
    ['typeIndex' => 5, 'emoji' => '😢'], // Sad
    ['typeIndex' => 6, 'emoji' => '😡']  // Angry
];

echo "Starting to insert Tunisia-related posts...\n";

// Insert posts, comments, and reactions
foreach ($tunisiaPosts as $index => $postData) {
    // Create post
    $post = new Post();
    $post->setTitle($postData['title']);
    $post->setContent($postData['content']);
    $post->setUser($author);
    $post->setLocation($postData['location']);
    $post->setEmail($postData['email']);
    $post->setNum($postData['num']);
    $post->setCreatedAt(new \DateTime());

    $entityManager->persist($post);
    $entityManager->flush(); // Flush to get the post ID

    echo "Created post: {$postData['title']}\n";

    // Add comments to the post
    foreach ($commentTexts[$index] as $commentText) {
        // Randomly select a user for the comment
        $commentUser = $users[array_rand($users)];

        $comment = new Comment();
        $comment->setContent($commentText);
        $comment->setUser($commentUser);
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTime());

        $entityManager->persist($comment);
        echo "  Added comment to post\n";
    }

    // Add reactions to the post
    // Shuffle reaction types to get random ones
    shuffle($reactionTypes);
    $selectedReactions = array_slice($reactionTypes, 0, 3);

    foreach ($selectedReactions as $reactionType) {
        // Randomly select a user for the reaction
        $reactionUser = $users[array_rand($users)];

        $reaction = new Reaction();
        $reaction->setPost($post);
        $reaction->setUser($reactionUser);
        $reaction->setTypeIndex($reactionType['typeIndex']);
        $reaction->setEmoji($reactionType['emoji']);

        $entityManager->persist($reaction);
        echo "  Added reaction to post: {$reactionType['emoji']}\n";
    }

    $entityManager->flush();
    $postNumber = $index + 1;
    echo "Completed post {$postNumber} of 10\n";
}

echo "Successfully inserted 10 Tunisia-related posts with comments and reactions!\n";
