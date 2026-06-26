<?php

require_once 'templates/header.php';
require_once 'libs/expenses.php';
require_once 'libs/categories.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
}


$categories = getCategories($pdo);
$showModalAddExpense = false;

$errors = [];

if (isset($_POST['saveExpense'])) {

    $title = trim($_POST['title']); // trim enlève l'espace au début et à la fin de la chaîne
    $amount = (float)$_POST['amount'];
    //Ternaire  ? remplace isset et : remplace else
    $idCategory = isset($_POST['category']) ? (int)$_POST['category'] : 0;  // isset sert à vérifier la condition si  la variable existe et qu'elle n'est pas NULL

    // $idCategory = isset($_POST['category']) ? (int)$_POST['category'] : 0; remplace le code commenté ci-dessous
    // if (isset($POST["category"])) {
    //     $idCategory = (int)$_POST['category'];
    // } else {
    //     $idCategory = 0; // Valeur par défaut si la catégorie n'est pas définie
    // }

    $date = $_POST['date'];

    $newCategory = trim($_POST['new_category'] ?? '');
    $idCategory = (int)$_POST['category'];

    if ($newCategory !== "") {
        addCategory($pdo, $newCategory);
        $idCategory = (int)$pdo->lastInsertId();
    }

    $errors = [];

    if (!is_numeric($amount) || $amount <= 0) {
        $errors["amount"] = "Le montant doit être supérieur à 0.";
    }

    if ($title === "") {
        $errors["title"] = "Le titre est obligatoire.";
    }

    if ($idCategory <= 0) {
        $errors["category"] = "La catégorie est obligatoire.";
    }

    // On en registre la dépense seulement s'il n'y a pas d'erreurs
    if (!$errors) {
        $res = saveExpense($pdo, $title, $amount, $date, $idCategory, $_SESSION['user']['id_user']);
        header("Location: depense.php?success=1"); //« Arrête cette page et recharge depense.php »
        exit;
    } else {
        $showModalAddExpense = true; // soumission, si erreur, on réaffiche le modal
    }
}
// déplacement de la récupération des dépenses après l'enregistrement pour s'assurer que la liste est à jour
$expenses = getExpenses($pdo, $_SESSION['user']['id_user']);
$expenseTotal = getExpensesTotal($pdo, $_SESSION['user']['id_user']);
// var_dump($expenseTotal);
?>

<div class="container col-xxl-8 px-4 py-5">
    <h1 class="mb-3">Mes dépenses</h1>

    <form method="GET">
        <table class="table">
            <thead>
                <tr class="table-dark">
                    <th scope="col">Titre</th>
                    <th scope="col">Montant</th>
                    <th scope="col">Date</th>
                    <th scope="col">
                        <select onchange="this.form.submit()" name="category" id="category" class="form-control form-select" aria-label="Catégorie">
                            <option value="">Catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= $category["id_category"] ?>"
                                    <?= (isset($_GET['category']) && (int)$_GET['category'] === $category["id_category"]) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category["name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= htmlspecialchars($expense["title"]) ?></td>
                        <td class=" text-right"><?= htmlspecialchars(formatPrice($expense["amount"])) ?></td>
                        <td><?= htmlspecialchars($expense["expense_date"]) ?></td>
                        <td><?= htmlspecialchars($expense["category_name"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            <tfoot>
                <tr class="table-dark">
                    <td>Total</td>
                    <td class=" text-right"><?= htmlspecialchars(formatPrice($expenseTotal[0])) ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
            </tbody>
        </table>
    </form>
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">
        Ajouter une dépense
    </button>

    <!-- Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="expenseModalLabel">Dépense</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" name="title" id="title" class="form-control">
                        </div>
                        <?php if (isset($errors["title"])): ?>
                            <div class="alert alert-danger">
                                <?= $errors["title"] ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Montant</label>
                            <input type="number" name="amount" id="amount" step=".01" class="form-control">
                        </div>
                        <?php if (isset($errors["amount"])): ?>
                            <div class="alert alert-danger">
                                <?= $errors["amount"] ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" name="date" id="date" class="form-control">
                        </div>
                        <!-- Category selection -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Catégorie</label>
                            <select name="category" id="category" class="form-control form-select">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category["id_category"] ?>"><?= $category["name"] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($errors["category"])): ?>
                            <div class="alert alert-danger">
                                <?= $errors["category"] ?>
                            </div>
                        <?php endif; ?>
                        <!-- New category input -->
                        <div class="mb-3 ml-3">
                            <label for="new_category" class="form-label">Ajouter une catégorie</label>
                            <input type="text" name="new_category" id="new_category" class="form-control  border border-1 border-secondary">
                        </div>
                    </div>
                    <!--Close modal body and add footer with buttons -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <input name="saveExpense" type="submit" class="btn btn-primary" value="Enregistrer">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Show the modal if there are errors
    <?php if ($showModalAddExpense): ?>

        document.addEventListener("DOMContentLoaded", function() {
            const expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
            expenseModal.show();
        });

    <?php endif; ?>
</script>


<?php require_once 'templates/footer.php'; ?>