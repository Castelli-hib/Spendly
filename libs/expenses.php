<?php

function getExpenses(PDO $pdo, int $userId): array
{
    $sql = "SELECT e.id_expense, e.amount, e.title, e.expense_date, e.id_category, c.name AS category_name
            FROM expense e
            JOIN category c ON e.id_category = c.id_category
            WHERE e.id_user = :userId";

    $query = $pdo->prepare($sql);
    $query->bindValue(':userId', $userId, PDO::PARAM_INT);
    $query->execute();

    return $query->fetchAll(PDO::FETCH_ASSOC);
}
