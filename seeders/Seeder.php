<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

// Chargement de .env s'il est présent
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

class Seeder {
    private static ?\PDO $pdo = null;

    private static function connect()
    {
        if (self::$pdo === null) {
            // Détection de l'environnement (Docker ou local)
            $isDocker = getenv('DOCKER_ENV') === 'true' || isset($_ENV['DOCKER_ENV']);
            
            if ($isDocker) {
                // Utiliser 'db' comme host dans Docker
                $dsn = 'pgsql:host=db;port=5432;dbname=gestion_auchan';
            } else {
                // Utiliser localhost en local ou récupérer depuis .env
                $dsn = getenv('DSN') ?: $_ENV['DSN'] ?? 'pgsql:host=localhost;port=5433;dbname=gestion_auchan';
            }
            
            $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
            $password = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? null;

            // Vérification que les variables sont définies
            if (empty($user)) {
                throw new \Exception("Variable d'environnement DB_USER non définie");
            }
            if (empty($password)) {
                throw new \Exception("Variable d'environnement DB_PASSWORD non définie");
            }

            echo "Tentative de connexion avec DSN: $dsn\n";
            
            try {
                self::$pdo = new \PDO($dsn, $user, $password);
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                echo "Connexion à la base de données réussie.\n";
            } catch (\PDOException $e) {
                echo "Erreur de connexion: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    public static function run()
    {
        $citoyens = [
    [
        'nom' => 'Diop',
        'prenom' => 'Amadou',
        'numerocni' => '1199912345678950',
        'photoidentite' => 'image.png',
        'lieuNaiss' => 'Dakar',
        'dateNaiss' => '1990-05-15'
    ]/* ,
    [
        'nom' => 'Fall',
        'prenom' => 'Fatou',
        'numerocni' => '1199987654321098',
        'photoidentite' => 'image.png',
        'lieuNaiss' => 'Saint-Louis',
        'dateNaiss' => '1995-08-22'
    ],
    [
        'nom' => 'Ndiaye',
        'prenom' => 'Moussa',
        'numerocni' => '1199955555555555',
        'photoidentite' => 'image.png',
        'lieuNaiss' => 'Thiès',
        'dateNaiss' => '1988-03-10'
    ],
    [
        'nom' => 'Gueye',
        'prenom' => 'Ramatoulaye',
        'numerocni' => 'CNI1090',
        'photoidentite' => null, 
        'lieuNaiss' => 'Dakar',
        'dateNaiss' => '1995-01-02'
    ] */
];

        self::connect();
        $cloud = require __DIR__ . '/../app/config/cloudinary.php';
            Configuration::instance([
            'cloud' => [
            'cloud_name' => $cloud['cloud_name'],
            'api_key' => $cloud['api_key'],
            'api_secret' => $cloud['api_secret'],
            ],
            'url' => ['secure' => true]
            ]);
            $cloudinary = new Cloudinary(Configuration::instance());


            foreach ($citoyens as $citoyen) {
            try {
            $imagePath = __DIR__ . '/images/' . $citoyen['photoidentite'];
            $upload = $cloudinary->uploadApi()->upload($imagePath, ['folder' => 'cni/recto']);
            $url = $upload['secure_url'];
            $stmt = self::$pdo->prepare("
            INSERT INTO citoyen (nom, prenom, dateNaiss,  lieuNaiss,  numerocni, photoidentite)
            VALUES (:nom, :prenom, :dateNaiss, :lieuNaiss, :numerocni, :photoidentite)");

            $stmt->execute([
            'nom' => $citoyen['nom'],
            'prenom' => $citoyen['prenom'],
            'dateNaiss' => $citoyen['dateNaiss'],
            'lieuNaiss' => $citoyen['lieuNaiss'],
            'numerocni' => $citoyen['numerocni'],
            'photoidentite' => $url
            
        ]); 
        
        } catch (Exception $e) {
                echo 'Erreur lors de lupload : ', $e->getMessage();
                }
                }    
    }
}

Seeder::run();