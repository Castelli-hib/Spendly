<?php

try {

    $dbSettings = parse_ini_file(_APP_ENV_);

    $dsn = "mysql:host={$dbSettings['db_host']};
            port={$dbSettings['db_port']};
            dbname={$dbSettings['db_name']};
            charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $dbSettings['db_user'],
        $dbSettings['db_password']
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Connexion réussie";

} catch (PDOException $e) {

    die("Erreur : " . $e->getMessage());

}