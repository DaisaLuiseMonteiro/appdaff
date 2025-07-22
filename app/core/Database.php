<?php

namespace App\Core;


use \PDO;
use \PDOException;

class Database{
    
    private $connection;
    private  static Database|null $instance = null;

      protected function __construct() {
        
        try {
           
            $this->connection = new PDO(
              dsn,
              DB_USER,
              DB_PASSWORD,
              [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
              ]
              );

             
        }catch(PDOException $e){
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection():PDO{
        return $this->connection;
    }


    
}