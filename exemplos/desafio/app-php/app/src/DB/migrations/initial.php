<?php
require __DIR__ . "/../../../vendor/autoload.php";

use App\DB\Database;
$db = Database::getInstance();
$db->exec("CREATE TABLE IF NOT EXISTS people (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        cpf INTEGER UNIQUE NOT NULL,
        birthDate TEXT NOT NULL,
        gender TEXT CHECK (gender IN ('M', 'F', 'O')) NOT NULL
    );
");

$db->exec("CREATE TABLE IF NOT EXISTS address (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        location TEXT NULL,
        number TEXT NULL,
        neighborhood TEXT NULL,
        city TEXT NULL,
        state TEXT NULL,
        reference TEXT NULL,
        idPeople INTEGER NOT NULL,
        FOREIGN KEY (idPeople) REFERENCES people(id) ON DELETE CASCADE
    );
");
