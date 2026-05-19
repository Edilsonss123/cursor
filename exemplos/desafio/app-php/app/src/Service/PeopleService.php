<?php
namespace App\Service;
use App\ValueObject\{ People, Address };
use App\Repository\{IPeopleRepository, PeopleRepositorySQLite};
use App\Repository\{IPeopleAddressRepository, PeopleAddressRepositorySQLite};
use App\Exception\CrudException;

class PeopleService
{
    private IPeopleRepository $peopleRepository;
    private IPeopleAddressRepository $peopleAddressRepository;
    public function __construct() {
        $this->peopleRepository = new PeopleRepositorySQLite();
        $this->peopleAddressRepository = new PeopleAddressRepositorySQLite();
    }

    public function getAll()
    {
        return $this->peopleRepository->getAll();
    }

    public function getFindById(int $id)
    {
        if ($id <= 0) {
            throw new CrudException("People Id '{$id}' invalid", 422);
        }
        return $this->peopleRepository->getFindById($id);
    }

    public function create(People $people)
    {
        $erros = $people->valid();
        if (!$this->peopleRepository->validCFPUnique($people->cpf, null)) {
            $erros[] = "CPF in use, CPF must be unique";
        }
        if (!empty($erros)) {
            throw new CrudException("Invalid payload", 422, null, $erros);
        }

        $id = $this->peopleRepository->create($people);
        if (!$people->address->isEmpty()) {
            $this->peopleAddressRepository->create($id, $people->address);
        }

        return $this->getFindById($id);
    }
    
    public function update(int $id, People $people)
    {
        $erros = $people->valid();
        if (!$this->peopleRepository->validCFPUnique($people->cpf, $id)) {
            $erros[] = "CPF in use, CPF must be unique";
        }
        if (!empty($erros)) {
            throw new CrudException("Invalid payload", 422, null, $erros);
        }
        
        if (!$this->getFindById($id)) {
            throw new CrudException("People not found", 404);
        }

        $people = $this->peopleRepository->update($id, $people);
        $this->peopleAddressRepository->update($id, $people->address);
        return $people;
    }

    public function delete(int $id)
    {
        if (!$this->getFindById($id)) {
           throw new CrudException("People not found", 404);
        }
        $this->peopleRepository->delete($id);
        $this->peopleAddressRepository->delete($id);
    }
}
