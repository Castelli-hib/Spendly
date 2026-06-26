<?php

function getCategories(PDO $pdo):array
{
    $query = $pdo->prepare("SELECT c.id_category, c.name
                            FROM category c");
    $query->execute();

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function addCategory(PDO $pdo, string $name):int
{
    $query = $pdo->prepare("
        INSERT INTO category (name)
        VALUES (:name)
    ");
    $query->bindValue(':name', $name, PDO::PARAM_STR);
    $query->execute();
    return $pdo->lastInsertId();
}