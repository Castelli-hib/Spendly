<?php

function getExpenses(PDO $pdo, int $userId): array
{
    $sql = "SELECT e.id_expense, e.amount, e.title, e.expense_date, e.id_category, c.name AS category_name
            FROM expense e
            JOIN category c ON e.id_category = c.id_category
            WHERE e.id_user = :userId
            ORDER BY e.expense_date ASC";

    $query = $pdo->prepare($sql);
    $query->bindValue(':userId', $userId, PDO::PARAM_INT);
    $query->execute();

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// SELECT SUM(amount) as total FROM `expense` expense;

function getExpensesTotal(PDO $pdo, int $userId): array
{
    $sql = "SELECT SUM(amount) as total 
            FROM expense 
            WHERE id_user = :userId";

    $query = $pdo->prepare($sql);  // $query une variable qui contient la requête préparée ou le résultat d'une requête préparée. C'est un objet PDOStatement qui permet d'exécuter la requête et de récupérer les résultats.
    $query->bindValue(':userId', $userId, PDO::PARAM_INT); // binValue copie la valeur immédiatement
    $query->execute();

    $result = $query->fetch(PDO::FETCH_ASSOC); // fetc() sert à récupérer UNE seule ligne de résultat, contrairement à fetchAll() qui récupère toutes les lignes.
    return (array)($result['total'] ?? 0);
}

function saveExpense(PDO $pdo, string $title, float $amount, string $expenseDate, int $idCategory, int $idUser):bool
{
    $query = $pdo->prepare("INSERT INTO expense (title, amount, expense_date, id_category, id_user)
                            VALUES (:title, :amount, :expense_date, :id_category, :id_user)");

    $query->bindValue(":title", $title, PDO::PARAM_STR);
    $query->bindValue(":amount", $amount, PDO::PARAM_STR);
    $query->bindValue(":expense_date", $expenseDate, PDO::PARAM_STR);
    $query->bindValue(":id_category", $idCategory, PDO::PARAM_INT);
    $query->bindValue(":id_user", $idUser, PDO::PARAM_INT);

    return $query->execute();
}
// formatPrice function, it formats a price value into a string with a specified number of decimal places and a currency symbol. It uses the number_format function to format the price with commas as thousands separators and a space as the decimal separator. The default currency symbol is set to "€", but it can be changed by passing a different value to the function. The formatted price is returned as a string.
function formatPrice(float|int $price, string $currency = "€", int $decimal = 2): string
{
    return number_format($price, $decimal, ',', ' ') . " " . $currency;
}