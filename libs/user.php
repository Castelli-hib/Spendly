<?php

function addUser(PDO $pdo, string $email, string $password) : bool
{
    // On prépare la requête
    $query = $pdo->prepare("INSERT INTO user (email, password) VALUES (:email, :password)");
    
    // On hash le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    //On passe les données
    $query->bindValue(':email', $email, PDO::PARAM_STR);
    $query->bindValue(':password', $hashedPassword, PDO::PARAM_STR);

    // On exécute la requête
    return $query->execute();

}

function verifyLogin(PDO $pdo, string $email, string $password) : bool|array
{
    // On prépare la requête
    $query = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $query->bindValue(':email', $email, PDO::PARAM_STR);
    // On exécute la requête
    $query->execute();

    // On récupère l'utilisateur
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user && password_verify($password, $user['password'])) {
        return $user;
    } else {
        return false;
    }

}