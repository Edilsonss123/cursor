<?php
namespace App\Repository;
use App\ValueObject\People;

interface IPeopleRepository
{
    public function getAll();
    public function validCFPUnique(int $id, int $cpf) : bool;
    public function getFindById(int $id);
    public function create(People $people) :int;
    public function update(int $id, People $people);
    public function delete(int $id);
}