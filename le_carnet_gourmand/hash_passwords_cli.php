<?php
// hash_passwords_cli.php
// Usage (Windows CMD / PowerShell):
//   php hash_passwords_cli.php               -> exécution (modifie la BDD)
//   php hash_passwords_cli.php --dry-run     -> simulation, aucune modification
//   php hash_passwords_cli.php --default=abc -> pour les comptes sans mot de passe, utilise 'abc' (hashé)

/*
 * CONFIG — adapte ces valeurs à ta configuration locale si nécessaire
 */
$dbHost = '127.0.0.1';
$dbName = 'le_carnet_gourmand';
$dbUser = 'root';
$dbPass = ''; // si root a un mot de passe, le mettre ici

// ---- Ne modifie rien en dessous sauf si tu sais ce que tu fais ----

$options = getopt("", ["dry-run", "default:"]);
$dryRun = isset($options['dry-run']);
$defaultPassword = $options['default'] ?? null;

echo "=== hash_passwords_cli.php ===\n";
echo "Mode: " . ($dryRun ? "DRY-RUN (no updates)" : "EXECUTE (will update DB)") . "\n";
if ($defaultPassword !== null) {
    echo "Default password for empty entries: '{$defaultPassword}'\n";
}
echo "\n";

// Connexion PDO
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "ERROR: Could not connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

// Vérifier colonne length (optionnel mais utile)
try {
    $colInfo = $pdo->query("SHOW COLUMNS FROM comptes LIKE 'mot_de_passe'")->fetch();
    if ($colInfo) {
        preg_match('/\((\d+)\)/', $colInfo['Type'] ?? '', $m);
        $len = $m[1] ?? null;
        if ($len !== null && (int)$len < 60) {
            echo "WARNING: colonne mot_de_passe length = {$len} — il est recommandé d'utiliser VARCHAR(255).\n";
            echo "You can run: ALTER TABLE comptes MODIFY mot_de_passe VARCHAR(255) NOT NULL;\n\n";
        }
    }
} catch (Throwable $e) {
    // ignore
}

// Récupérer tous les comptes
$stmt = $pdo->query("SELECT pk_compte, email, mot_de_passe FROM comptes");
$rows = $stmt->fetchAll();

$total = count($rows);
$skippedAlreadyHashed = 0;
$skippedEmpty = 0;
$updated = 0;
$errors = 0;

echo "Found {$total} comptes. Processing...\n\n";

foreach ($rows as $row) {
    $id = $row['pk_compte'];
    $email = $row['email'] ?? '(no-email)';
    $pwd = $row['mot_de_passe'];

    // Détecter hash bcrypt (commence par $2y$ / $2b$ / $2a$) — prudence
    $isHashed = is_string($pwd) && preg_match('/^\$2[aby]\$/', $pwd);

    if ($isHashed) {
        $skippedAlreadyHashed++;
        echo "[SKIP] #{$id} {$email} — already hashed\n";
        continue;
    }

    // Si vide / null
    if ($pwd === null || $pwd === '') {
        if ($defaultPassword === null) {
            $skippedEmpty++;
            echo "[SKIP] #{$id} {$email} — empty/null password (no default provided)\n";
            continue;
        } else {
            $plain = $defaultPassword;
            echo "[DEFAULT] #{$id} {$email} — using default password\n";
        }
    } else {
        $plain = $pwd;
        echo "[HASH ] #{$id} {$email} — hashing existing cleartext\n";
    }

    // Hasher
    $hashed = password_hash($plain, PASSWORD_DEFAULT);
    if ($hashed === false) {
        $errors++;
        echo "[ERROR] #{$id} failed to hash\n";
        continue;
    }

    if ($dryRun) {
        echo "        (dry-run) would update pk={$id} mot_de_passe => " . substr($hashed,0,20) . "...\n";
        $updated++;
        continue;
    }

    // Update en base dans une requête préparée
    try {
        $up = $pdo->prepare("UPDATE comptes SET mot_de_passe = :hash WHERE pk_compte = :id");
        $up->execute([':hash' => $hashed, ':id' => $id]);
        $updated++;
        echo "        updated.\n";
    } catch (Throwable $e) {
        $errors++;
        echo "[ERROR] #{$id} update failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total: $total\n";
echo "Already hashed (skipped): $skippedAlreadyHashed\n";
echo "Empty/null (skipped): $skippedEmpty\n";
echo "Updated: $updated\n";
echo "Errors: $errors\n";
echo "Dry-run: " . ($dryRun ? "yes" : "no") . "\n";
echo "================\n";
