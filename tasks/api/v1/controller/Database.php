<?php

class Database {
    private static $dbConnection;

    public static function connectDb() {
        if(self::$dbConnection === null) {
            // connect string, username, password
            self::$dbConnection = new PDO('mysql:host=localhost;dbname=nukular_db;charset=utf8', 'root', 'root');
            self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // mysql can handle prepared statments on its own
            self::$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }

        return self::$dbConnection;
    }
}

?>