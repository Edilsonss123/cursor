<?php
namespace App\DB;

class Database
{
    public static $db;
    public static function getInstance()
    {
        if (!static::$db) {
            static::$db = new \SQLite3('./sqlite/crud-com-eduardo.db', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        }
        return static::$db;
    }
}
