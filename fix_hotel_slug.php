<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Database connection parameters
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? 'inno';
$user = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to the database successfully.\n";
    
    // Check if slug column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM hotel LIKE 'slug'");
    $slugColumnExists = $stmt->rowCount() > 0;
    
    if (!$slugColumnExists) {
        // Add the slug column without unique constraint
        $pdo->exec("ALTER TABLE hotel ADD COLUMN slug VARCHAR(255)");
        echo "Added slug column to hotel table.\n";
    } else {
        echo "Slug column already exists.\n";
    }
    
    // Update all hotels to have a unique slug based on name and ID
    $stmt = $pdo->query("SELECT id, name FROM hotel");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($hotels as $hotel) {
        $id = $hotel['id'];
        $name = $hotel['name'];
        
        // Create a slug from the name (lowercase, replace spaces with hyphens)
        $slug = strtolower(str_replace([' ', '.', ','], '-', $name)) . '-' . $id;
        
        // Update the hotel record
        $updateStmt = $pdo->prepare("UPDATE hotel SET slug = :slug WHERE id = :id");
        $updateStmt->execute(['slug' => $slug, 'id' => $id]);
    }
    
    echo "Updated all hotels with unique slugs.\n";
    
    // Check if unique constraint exists
    $stmt = $pdo->query("SHOW INDEX FROM hotel WHERE Key_name = 'UNIQ_3535ED9989D9B62'");
    $uniqueConstraintExists = $stmt->rowCount() > 0;
    
    if (!$uniqueConstraintExists) {
        // Add the unique constraint
        $pdo->exec("ALTER TABLE hotel MODIFY COLUMN slug VARCHAR(255) NOT NULL");
        $pdo->exec("CREATE UNIQUE INDEX UNIQ_3535ED9989D9B62 ON hotel (slug)");
        echo "Added NOT NULL constraint and unique index to slug column.\n";
    } else {
        echo "Unique constraint already exists.\n";
    }
    
    echo "Hotel slug column has been fixed successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
