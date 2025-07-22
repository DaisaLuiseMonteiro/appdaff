<?php
namespace App\Core\Abstract;
use App\Core\Database;

use \PDO;

abstract class AbstractRepository extends Database{

    protected PDO $pdo;

    protected function __construct(){
        $this->pdo = parent::getInstance()->getConnection();
    }

    abstract public function selectAll();
    abstract public function insert(array $data);
    abstract public function update();
    abstract public function delete();
    abstract public function selectById($id);
    abstract public function selectBy(array $filter);


}