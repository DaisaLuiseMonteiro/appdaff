<?php

require_once  __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Chargement de .env s'il est présent (utile en local)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

class Migration
{
    private static ?\PDO $pdo = null;

    private static function connect()
    {
        if (self::$pdo === null) {
            // ✅ Correction : utiliser 'DSN' au lieu de 'dsn'
            $dsn = getenv('DSN') ?: $_ENV['DSN'] ?? null;
            $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
            $password = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? null;
            
            // Vérification que les variables sont définies
            if (empty($dsn)) {
                throw new \Exception("Variable d'environnement DSN non définie");
            }
            if (empty($user)) {
                throw new \Exception("Variable d'environnement DB_USER non définie");
            }
            if (empty($password)) {
                throw new \Exception("Variable d'environnement DB_PASSWORD non définie");
            }
            
            self::$pdo = new \PDO($dsn, $user, $password);
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    private static function getQueries(): array
    {
        $driver = self::$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            return [
                "CREATE TABLE IF NOT EXISTS citoyen (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    nom VARCHAR(100) NOT NULL,
                    prenom VARCHAR(100) NOT NULL,
                    numerocni VARCHAR(20) UNIQUE,
                    photoidentite TEXT,
                    lieuNaiss VARCHAR(100),
                    dateNaiss DATE
                )",
                "CREATE TABLE IF NOT EXISTS journalisation (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    localisation TEXT,
                    ipadress VARCHAR(50),
                    status BOOLEAN DEFAULT false,
                    citoyenId INTEGER UNSIGNED NOT NULL,
                    FOREIGN KEY (citoyenId) REFERENCES citoyen(id)
                )"
            ];
        } else {
            return [
                "CREATE TABLE IF NOT EXISTS citoyen (
                    id SERIAL PRIMARY KEY,
                    nom VARCHAR(100) NOT NULL,
                    prenom VARCHAR(100) NOT NULL,
                    numerocni VARCHAR(20) UNIQUE,
                    photoidentite TEXT,
                    lieuNaiss VARCHAR(100),
                    dateNaiss DATE
                )",
                "CREATE TABLE IF NOT EXISTS journalisation (
                    id SERIAL PRIMARY KEY,
                    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    localisation TEXT,
                    ipadress VARCHAR(50),
                    status BOOLEAN DEFAULT false,
                    citoyenId INTEGER REFERENCES citoyen(id)
                )"
            ];
        }
    }

    public static function up()
    {
        self::connect();
        $queries = self::getQueries();

        foreach ($queries as $sql) {
            try {
                self::$pdo->exec($sql);
                echo "Requête exécutée avec succès.\n";
            } catch (\PDOException $e) {
                echo "Erreur lors de l'exécution de la requête: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        echo "Migration terminée avec succès.\n";
    }
}

Migration::up();