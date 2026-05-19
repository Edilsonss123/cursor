<?php
namespace App\Repository;
use App\ValueObject\Address;
use App\DB\Database;

class PeopleAddressRepositorySQLite implements IPeopleAddressRepository
{
    private $database;
    public function __construct() {
        $this->database = Database::getInstance();
    }

    public function create(int $idPeople, Address $address) :int
    {
        $sql = "INSERT INTO address (location, number, neighborhood, city, state, reference, idPeople) 
            VALUES (:location, :number, :neighborhood, :city, :state, :reference, :idPeople)";
        $stmt = $this->database->prepare($sql);

        $stmt->bindValue(':location', $address->location, SQLITE3_TEXT); 
        $stmt->bindValue(':number', $address->number, SQLITE3_TEXT); 
        $stmt->bindValue(':neighborhood', $address->neighborhood, SQLITE3_TEXT); 
        $stmt->bindValue(':city', $address->city, SQLITE3_TEXT); 
        $stmt->bindValue(':state', $address->state, SQLITE3_TEXT); 
        $stmt->bindValue(':reference', $address->reference, SQLITE3_TEXT); 
        $stmt->bindValue(':idPeople', $idPeople, SQLITE3_INTEGER);
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->create: " . $this->database->lastErrorMsg());
        }
        return $this->database->lastInsertRowID();
    }
    
    public function update(int $idPeople, Address $address)
    {
        $sql = "UPDATE address SET location = :location, number = :number, neighborhood = :neighborhood, 
        city = :city, state = :state, reference = :reference WHERE idPeople = :idPeople";

        $stmt = $this->database->prepare($sql);
        $stmt->bindValue(':location', $address->location, SQLITE3_TEXT); 
        $stmt->bindValue(':number', $address->number, SQLITE3_TEXT); 
        $stmt->bindValue(':neighborhood', $address->neighborhood, SQLITE3_TEXT); 
        $stmt->bindValue(':city', $address->city, SQLITE3_TEXT); 
        $stmt->bindValue(':state', $address->state, SQLITE3_TEXT); 
        $stmt->bindValue(':reference', $address->reference, SQLITE3_TEXT); 
        $stmt->bindValue(':idPeople', $idPeople, SQLITE3_INTEGER);
        
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->update: " . $this->database->lastErrorMsg());
        }
        return $people;
    }

    public function delete(int $idPeople)
    {
        $sql = "DELETE FROM address WHERE idPeople = :idPeople";
        $stmt = $this->database->prepare($sql);
        $stmt->bindValue(':idPeople', $id, SQLITE3_INTEGER);
        if ($stmt->execute() === false) {
            throw new \Exception(get_class($this)."->delete: " . $this->database->lastErrorMsg());
        }
    }
}
