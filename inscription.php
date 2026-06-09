<?php 
require_once 'templates/header.php'; 
require_once 'libs/user.php';

$errors = [];

if (isset($_POST["email"]) && isset($_POST["password"])) {
    if (empty($_POST["email"])) {
        $errors[] = "L'email est obligatoire";
    }
    if (empty($_POST["password"])) {
        $errors[] = "Le mot de passe est obligatoire";
    }
    if (count($errors) === 0) {
        $res =addUser($pdo, $_POST["email"], $_POST["password"]);
        if ($res) {
            header("Location: login.php");
        } else {
            $errors[] = "Une erreur est survenue lors de l'inscription";
        }
    }
}
?>

        <div class="container col-xxl-8 px-4 py-5">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <h1 >Inscription</h1>

        <?php foreach ($errors as $key => $error): ?>
            <div class="alert alert-danger">
                <?= $error  ?>
            </div>
        <?php endforeach; ?>

                <form action="inscription.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-outline-secondary btn-lg px-4 me-md-2">S'inscrire</button>
                </form>
 
            </div>
        </div>

<?php require_once 'templates/footer.php'; ?>