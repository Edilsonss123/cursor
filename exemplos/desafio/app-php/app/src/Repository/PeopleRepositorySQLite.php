<?php
namespace App\Repository;
use App\ValueObject\People;
use App\DB\Database;

class PeopleRepositorySQLite implements IPeopleRepository
{
    private $database;
    public function __construct() {
        $this->database = Database::getInstance();
    }

    public function getAll()
    {
        $statement = $this->database->prepare('SELECT 
            people.*, 
            address.id AS idAddress,
            address.location,
            address.number,
            address.neighborhood,
            address.city,
            address.state,
            address.reference
            FROM people 
            LEFT JOIN address 
                ON address.idPeople = people.id;
        ');

        $result = $statement->execute();
        if ($result === false) {
            throw new \Exception(get_class($this)."->getAll: " . $this->database->lastErrorMsg());
        }

        $peoples = [];
    
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $peoples[] = People::fromArray($row);
        }
        
        return $peoples;
    }

    public function getFindById(int $id)
    {
        $statement = $this->database->prepare('SELECT 
            people.*, 
            address.id AS idAddress,
            address.location,
            address.number,
            address.neighborhood,
            address.city,
            address.state,
            address.reference
            FROM people 
            LEFT JOIN address 
                ON address.idPeople = people.id
            WHERE people.id = :id;
        ');

        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        if ($result === false) {
            throw new \Exception(get_class($this)."->getFindById: " . $this->database->lastErrorMsg());
        }
        $people = $result->fetchArray(SQLITE3_ASSOC);
        return $people ? People::fromArray($people): null;
    }

    public function validCFPUnique(int $cpf, ?int $id=null) : bool
    {
        $sql = 'SELECT people.id FROM people WHERE cpf = :cpf ';
        if ($id) {
            $sql .= ' AND id != :id';
        }
        $statement = $this->database->prepare($sql);

        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $statement->bindValue(':cpf', $cpf, SQLITE3_INTEGER);
        $result = $statement->execute();
        if ($result === false) {
            throw new \Exception(get_class($this)."->validCFPUnique: " . $this->database->lastErrorMsg());
        }
        $people = $result->fetchArray(SQLITE3_ASSOC);
        return !$people;
    }

    public function create(People $people) :int
    {
        $sql = "INSERT INTO people (name, cpf, birthDate, gender) VALUES (:name, :cpf, :birthDate, :gender)";
        $stmt = $this->database->prepare($sql);
        $stmt->bindValue(':name', $people->name);
        $stmt->bindValue(':cpf', $people->cpf, SQLITE3_TEXT);
        $stmt->bindValue(':birthDate', $people->birthDate, SQLITE3_TEXT);
        $stmt->bindValue(':gender', $people->gender, SQLITE3_TEXT);
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->create: " . $this->database->lastErrorMsg());
        }
        return $this->database->lastInsertRowID();
    }
    
    public function update(int $id, People $people)
    {
        $sql = "UPDATE people SET name = :name, cpf = :cpf, birthDate = :birthDate, gender = :gender WHERE id = :id";
        $stmt = $this->database->prepare($sql);
        $stmt->bindValue(':name', $people->name, SQLITE3_TEXT);
        $stmt->bindValue(':cpf', $people->cpf, SQLITE3_TEXT);
        $stmt->bindValue(':birthDate', $people->birthDate, SQLITE3_TEXT);
        $stmt->bindValue(':gender', $people->gender, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->update: " . $this->database->lastErrorMsg());
        }
        return $people;
    }
    
    public function delete(int $id)
    {
        $sql = "DELETE FROM people WHERE id = :id";
        $stmt = $this->database->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->delete: " . $this->database->lastErrorMsg());
        }
    }
}
