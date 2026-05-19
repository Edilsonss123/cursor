<?php
namespace App\ValueObject;
use App\Exception\CrudException;

readonly class People implements \JsonSerializable 
{
    public ?int $id;
    public string $name;
    public int $cpf;
    public string $birthDate;
    public string $gender;
    public Address $address;

    public function __construct(string $name, int $cpf, \DateTime $birthDate, string $gender, Address $address, ?int $id) 
    {
        $this->id = $id;
        $this->name = trim($name);
        $this->cpf = $cpf;
        $this->birthDate = $birthDate->format('Y-m-d');
        $this->gender = $gender;
        $this->address = $address;
    }

    public function valid() : array
    {
        $erros = [];
        $erros[] = $this->validateCPF();
        $erros[] = $this->validateGender();
        $erros[] = $this->validateName();
        $erros = array_merge($erros, $this->address->valid());
        $erros = array_values(array_filter($erros));
        return $erros;
    }
    
    private function validateName()
    {
        if (!$this->name) {
            return "Name required";
        }
    }

    private function validateGender()
    {
        $validGenders = ['M', 'F', 'O'];
        if (!in_array($this->gender, $validGenders)) {
            return "Gender must be 'M', 'F' or 'O'.";
        }
    }
    private function validateCPF()
    {
        if (!validateCPF($this->cpf)) {
            return "CPF invalid";
        }
    }
    public function __toString()
    {
        return "{$this->name}, {$this->birthDate}, {$this->gender}, {$this->cpf}, Address: {$this->address}";
    }

    public function jsonSerialize(): array
    {
        $id = $this->id ? ['id' => $this->id]: [];
        return array_merge($id, [
            'name' => $this->name,
            'cpf' => $this->cpf,
            'birthDate' => $this->birthDate,
            'gender' => $this->gender,
            'address' => $this->address
        ]);
    }

    public function isEmpty() : bool {
        return empty(array_filter($this->jsonSerialize()));
    }

    public static function fromArray(array $data): self
    {
        $id = intval($data['id'] ?? 0) > 0 ? $data['id']: null;
        $idAddress = intval($data['idAddress'] ?? 0) > 0 ? $data['idAddress']: null;

        return new self(
            $data['name'] ?? "",
            (int) ($data['cpf'] ?? ""),
            new \DateTime($data['birthDate'] ?? ""),
            $data['gender'] ?? "",
            Address::fromArray($data['address'] ?? [
                "location" => $data["location"] ?? "",
                "number" => $data["number"] ?? "",
                "neighborhood" => $data["neighborhood"] ?? "",
                "city" => $data["city"] ?? "",
                "state" => $data["state"] ?? "",
                "reference" => $data["reference"] ?? "",
                "id" => $idAddress
            ]),
            $id
        );
    }
}
