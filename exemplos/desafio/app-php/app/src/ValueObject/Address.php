<?php
namespace App\ValueObject;

readonly class Address implements \JsonSerializable
{
    public ?int $id;
    public string $location;
    public string $number;
    public string $neighborhood;
    public string $city;
    public string $state;
    public string $reference;

    public function __construct($location, $number, $neighborhood, $city, $state, $reference = null, ?int $id=null) 
    {
        $this->id = $id;
        $this->location = trim($location);
        $this->number = trim($number);
        $this->neighborhood = trim($neighborhood);
        $this->city = trim($city);
        $this->state = trim($state);
        $this->reference = trim($reference);
    }

    public function valid() : array
    {
        return [];
    }

    public function isEmpty() : bool {
        return empty(array_filter($this->jsonSerialize()));
    }
    
    public function __toString() {
        $address = $this->location . ', ' . $this->number . ', ' . $this->neighborhood . ', ' . $this->city . ', ' . $this->state;
        if ($this->reference) {
            $address .= ' - ' . $this->reference;
        }
        return $address;
    }

    public function jsonSerialize(): array 
    {
        $id = !empty($this->id) ? ['id' => $this->id]: [];
        return array_merge($id, [
            'location' => $this->location,
            'number' => $this->number,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'reference' => $this->reference,
            'address' => !empty($this->id) ? $this->__toString(): ""
        ]);
    }

    public static function fromArray(array $data): self
    {
        $id = intval($data['id'] ?? 0) > 0 ? $data['id']: null;
        return new self(
            $data['location'] ?? "",
            $data['number'] ?? "",
            $data['neighborhood'] ?? "",
            $data['city'] ?? "",
            $data['state'] ?? "",
            $data['reference'] ?? "",
            $id
        );
    }

}
