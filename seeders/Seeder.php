<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

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
        self::connect();

        try {
            // Insertion des données de test pour les tables citoyen et journalisation
            echo "Insertion des données de test...\n";
            
            // Insertion de citoyens
            self::$pdo->exec("INSERT INTO citoyen (nom, prenom, numerocni, photoidentite, lieuNaiss, dateNaiss) VALUES 
                ('Diop', 'Amadou', '1199912345678901', 'photo_amadou.jpg', 'Dakar', '1990-05-15'),
                ('Fall', 'Fatou', '1199987654321098', 'photo_fatou.jpg', 'Saint-Louis', '1995-08-22'),
                ('Ndiaye', 'Moussa', '1199955555555555', 'photo_moussa.jpg', 'Thiès', '1988-03-10')
            ON CONFLICT (numerocni) DO NOTHING");
            echo "Citoyens insérés.\n";

            // Insertion des journalisations (récupérer les IDs des citoyens)
            $stmt = self::$pdo->query("SELECT id FROM citoyen LIMIT 3");
            $citoyenIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($citoyenIds) >= 3) {
                self::$pdo->exec("INSERT INTO journalisation (date, heure, localisation, ipadress, status, citoyenId) VALUES 
                    ('2024-01-15 09:30:00', '2024-01-15 09:30:00', 'Dakar - Bureau des CNI', '192.168.1.10', true, {$citoyenIds[0]}),
                    ('2024-01-16 14:15:00', '2024-01-16 14:15:00', 'Saint-Louis - Mairie', '192.168.2.20', true, {$citoyenIds[1]}),
                    ('2024-01-17 11:45:00', '2024-01-17 11:45:00', 'Thiès - Préfecture', '192.168.3.30', false, {$citoyenIds[2]})");
                echo "Journalisations insérées.\n";
            }

            echo "Toutes les données de test ont été insérées avec succès.\n";

        } catch (PDOException $e) {
            echo "Erreur lors de l'insertion des données: " . $e->getMessage() . "\n";
            
            // Afficher plus de détails sur l'erreur
            if ($e->getCode() === '42P01') {
                echo "Erreur: Une ou plusieurs tables n'existent pas. Assurez-vous d'avoir exécuté les migrations d'abord.\n";
            }
            
            throw $e;
        }
    }
}

Seeder::run();