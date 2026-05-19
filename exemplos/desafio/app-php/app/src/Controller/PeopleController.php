<?php
namespace App\Controller;
use App\Service\PeopleService;
use App\ValueObject\People;
use App\Exception\CrudException;

class PeopleController
{
    private PeopleService $peopleService;
    public function __construct() {
        $this->peopleService = new PeopleService();
    }

    public function getAll()
    {
        return response("Peoples", $this->peopleService->getAll());
    }

    public function getFindById()
    {
        $id = intval($_GET["id"] ?? 0);
        $people = $this->peopleService->getFindById($id);
        if (!$people) {
            throw new CrudException("People not found", 404);
        }
        return response("People", $people);
    }

    public function create()
    {
        $data = getBodyRequest();
        $people = $this->peopleService->create(People::fromArray($data));
        return response("People created", $people);
    }
    
    public function update()
    {
        $id = intval($_GET["id"] ?? 0);
        $data = getBodyRequest();
        $people = $this->peopleService->update($id, People::fromArray($data));
        return response("People updated", $people);
    }

    public function delete()
    {
        $id = intval($_GET["id"] ?? 0);
        $this->peopleService->delete($id);
        return response("People deleted", []);
    }
}
