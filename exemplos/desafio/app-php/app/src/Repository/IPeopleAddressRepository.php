<?php
namespace App\Repository;
use App\ValueObject\Address;

interface IPeopleAddressRepository
{
    public function create(int $idPeople, Address $address) :int;
    public function update(int $idPeople, Address $address);
    public function delete(int $idPeople);
}