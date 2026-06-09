<?php
require_once 'libs/config.php';
require_once 'libs/pdo.php';

//Demarre une session pour stocker les données de l'utilisateur connecté
session_start();

$_SESSION['test'] = "test";

$mainMenu = [
    "index.php" => "Accueil",
    "Home.php" => "Maison",
    "charges.php" => "Charges",
    "courant.php" => "Courant",
];
if (isset($_SESSION['user'])) {
    $mainMenu["depense.php"] = "Mes dépenses";
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/override-bootstrap.css">
    <title>Spendly, Gestion des dépenses</title>
</head>

<body>
    <!-- Début navbar -->
    <div class="container">
        <header class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom">
            <div class="col-md-3 mb-2 mb-md-0">
                <a href="/" class="d-inline-flex link-body-emphasis text-decoration-none">
                    <img src="assets/images/spendly.svg" alt="Spendly Logo" width="150">
                </a>
                <?php if (isset($_SESSION['user'])) { ?>
                    <p class="mt-2 mb-0 small">
                        Bonjour, <?= $_SESSION['user']['email']; ?>
                    </p>
                <?php } ?>
            </div>

            <!-- Menu de navigation -->
            <ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">

                <?php foreach ($mainMenu as $page  => $title) { ?>

                    <?php
                    // Page actuelle
                    $isActive = basename($_SERVER['PHP_SELF']) === $page;
                    ?>

                    <li class="nav-item px-3 custom-nav-link <?= $isActive ? 'active-link' : '' ?>">
                        <a href="<?= $page; ?>" class="nav-link px-2"><?= $title; ?></a>
                    </li>
                <?php } ?>
            </ul>
            <!-- Fin du foreach -->


            <div class="col-md-3 text-end">
                <?php if (isset($_SESSION['user'])) { ?>

                    <!--<p class="d-inline me-2">Bonjour, <?= $_SESSION['user']['email']; ?></p>-->
                    <a href="logout.php" class="btn btn-outline-primary me-2">Se déconnecter</a>
                <?php } else { ?>

                    <a href="inscription.php" class="btn btn-outline-primary me-2">S'inscrire</a>
                    <a href="login.php" class="btn btn-primary">Se connecter</a>
                <?php } ?>
            </div>
        </header>
    </div>
    <!-- Fin navbar -->

    <!-- Début main -->
    <main>