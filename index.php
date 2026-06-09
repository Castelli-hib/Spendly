<?php

declare(strict_types=1);

session_start();

$dataDirectory = __DIR__ . '/data';
$dataFile = $dataDirectory . '/expenses.json';

if (!is_dir($dataDirectory)) {
    mkdir($dataDirectory, 0775, true);
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function loadData(string $file): array
{
    $default = [
        'settings' => [
            'currency' => '€',
            'monthly_budget' => 0.0,
            'categories' => ['Alimentation', 'Transport', 'Loisirs', 'Santé', 'Maison', 'Autre'],
        ],
        'expenses' => [],
    ];

    if (!file_exists($file)) {
        return $default;
    }

    $content = file_get_contents($file);
    if ($content === false || $content === '') {
        return $default;
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return $default;
    }

    return array_replace_recursive($default, $decoded);
}

function saveData(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function redirectWithoutPost(): void
{
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$data = loadData($dataFile);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Requête invalide, merci de réessayer.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save_settings') {
            $currency = trim((string) ($_POST['currency'] ?? '€'));
            $budgetInput = str_replace(',', '.', trim((string) ($_POST['monthly_budget'] ?? '0')));
            $categoriesInput = trim((string) ($_POST['categories'] ?? ''));
            $budget = is_numeric($budgetInput) ? (float) $budgetInput : 0.0;
            $categories = array_values(array_filter(array_unique(array_map('trim', explode(',', $categoriesInput)))));

            if ($currency === '' || mb_strlen($currency) > 8) {
                $errors[] = 'La devise est invalide.';
            }
            if ($budget < 0) {
                $errors[] = 'Le budget mensuel doit être positif.';
            }
            if (count($categories) === 0) {
                $errors[] = 'Ajoutez au moins une catégorie.';
            }

            if (count($errors) === 0) {
                $data['settings']['currency'] = $currency;
                $data['settings']['monthly_budget'] = round($budget, 2);
                $data['settings']['categories'] = $categories;
                saveData($dataFile, $data);
                redirectWithoutPost();
            }
        } elseif ($action === 'add_expense') {
            $amountInput = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '0')));
            $date = trim((string) ($_POST['expense_date'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $label = trim((string) ($_POST['label'] ?? ''));
            $amount = is_numeric($amountInput) ? (float) $amountInput : 0.0;

            $dateValid = DateTime::createFromFormat('Y-m-d', $date) !== false;
            if ($amount <= 0) {
                $errors[] = 'Le montant doit être supérieur à 0.';
            }
            if (!$dateValid) {
                $errors[] = 'La date est invalide.';
            }
            if (!in_array($category, $data['settings']['categories'], true)) {
                $errors[] = 'Catégorie invalide.';
            }
            if ($label === '' || mb_strlen($label) > 120) {
                $errors[] = 'Le libellé est requis (120 caractères max).';
            }

            if (count($errors) === 0) {
                $data['expenses'][] = [
                    'id' => bin2hex(random_bytes(8)),
                    'label' => $label,
                    'amount' => round($amount, 2),
                    'date' => $date,
                    'category' => $category,
                ];
                saveData($dataFile, $data);
                redirectWithoutPost();
            }
        } elseif ($action === 'delete_expense') {
            $id = (string) ($_POST['id'] ?? '');
            $data['expenses'] = array_values(array_filter(
                $data['expenses'],
                static fn(array $expense): bool => ($expense['id'] ?? '') !== $id
            ));
            saveData($dataFile, $data);
            redirectWithoutPost();
        }
    }
}

$selectedMonth = (string) ($_GET['month'] ?? date('Y-m'));
$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$monthValid = preg_match('/^\d{4}-\d{2}$/', $selectedMonth) === 1;
if (!$monthValid) {
    $selectedMonth = date('Y-m');
}

$filteredExpenses = array_values(array_filter(
    $data['expenses'],
    static function (array $expense) use ($selectedMonth, $selectedCategory): bool {
        $matchesMonth = str_starts_with((string) ($expense['date'] ?? ''), $selectedMonth);
        $matchesCategory = $selectedCategory === '' || (string) ($expense['category'] ?? '') === $selectedCategory;
        return $matchesMonth && $matchesCategory;
    }
));

usort(
    $filteredExpenses,
    static fn(array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''))
);

$totalMonth = array_reduce(
    $filteredExpenses,
    static fn(float $sum, array $expense): float => $sum + (float) ($expense['amount'] ?? 0),
    0.0
);
$budget = (float) ($data['settings']['monthly_budget'] ?? 0);
$remaining = $budget - $totalMonth;
$currency = (string) ($data['settings']['currency'] ?? '€');
$categoriesString = implode(', ', $data['settings']['categories']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendly - Dépenses quotidiennes</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;max-width:980px;margin:0 auto;padding:1.25rem}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem}
        label{display:block;font-size:.9rem;margin-bottom:.25rem}
        input,select,button{width:100%;padding:.55rem;border:1px solid #cbd5e1;border-radius:6px}
        button{background:#0f766e;color:#fff;border:none;cursor:pointer}
        table{width:100%;border-collapse:collapse}
        th,td{padding:.55rem;border-bottom:1px solid #e2e8f0;text-align:left}
        .danger{background:#b91c1c}
        .summary{display:flex;gap:1rem;flex-wrap:wrap}
        .error{color:#b91c1c;margin-bottom:.5rem}
    </style>
</head>
<body>
    <h1>Spendly</h1>
    <p>Gestionnaire de dépenses courantes personnalisé.</p>

    <?php foreach ($errors as $error): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <section class="card">
        <h2>Personnalisation</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_settings">
            <div class="grid">
                <div>
                    <label for="currency">Devise</label>
                    <input id="currency" name="currency" value="<?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label for="monthly_budget">Budget mensuel</label>
                    <input id="monthly_budget" name="monthly_budget" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $budget, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
            <label for="categories">Catégories (séparées par des virgules)</label>
            <input id="categories" name="categories" value="<?= htmlspecialchars($categoriesString, ENT_QUOTES, 'UTF-8') ?>" required>
            <br>
            <button type="submit">Enregistrer la personnalisation</button>
        </form>
    </section>

    <section class="card">
        <h2>Ajouter une dépense</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add_expense">
            <div class="grid">
                <div>
                    <label for="label">Libellé</label>
                    <input id="label" name="label" maxlength="120" required>
                </div>
                <div>
                    <label for="amount">Montant</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" required>
                </div>
                <div>
                    <label for="expense_date">Date</label>
                    <input id="expense_date" name="expense_date" type="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label for="category">Catégorie</label>
                    <select id="category" name="category" required>
                        <?php foreach ($data['settings']['categories'] as $category): ?>
                            <option value="<?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <br>
            <button type="submit">Ajouter</button>
        </form>
    </section>

    <section class="card">
        <h2>Suivi</h2>
        <form method="get" class="grid">
            <div>
                <label for="month">Mois</label>
                <input id="month" name="month" type="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <label for="filter_category">Catégorie</label>
                <select id="filter_category" name="category">
                    <option value="">Toutes</option>
                    <?php foreach ($data['settings']['categories'] as $category): ?>
                        <option value="<?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="align-self:end">
                <button type="submit">Filtrer</button>
            </div>
        </form>
        <div class="summary">
            <strong>Total: <?= number_format($totalMonth, 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong>
            <strong>Budget: <?= number_format($budget, 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong>
            <strong>Restant: <?= number_format($remaining, 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <br>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Catégorie</th>
                    <th>Montant</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($filteredExpenses) === 0): ?>
                    <tr><td colspan="5">Aucune dépense pour ce filtre.</td></tr>
                <?php else: ?>
                    <?php foreach ($filteredExpenses as $expense): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($expense['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($expense['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($expense['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float) ($expense['amount'] ?? 0), 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Supprimer cette dépense ?');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete_expense">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($expense['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="danger" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
