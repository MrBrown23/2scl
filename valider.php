<?php
// Activation des rapports d'erreurs
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


// Connexion à la base de données
$pdo = new PDO("mysql:host=localhost;dbname=odd_db;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Démarrage de la session
session_start();

// Récupération des informations utilisateur
$id_user = $_SESSION['id_user'] ?? 1;
$id_ville = $_SESSION['id_ville'] ?? 1;
$annee = date('Y');
$id_odd = isset($_POST['id_odd']) ? (int)$_POST['id_odd'] : 1;
// $indicateur_results = isset($_POST['indicateur_results']) 
//     ? array_map(function($value) { return $value === '' ? null : $value; }, $_POST['indicateur_results'])
//     : [];

$indicateur_results = $_POST['indicateur_results'] ?? [];
$indicateur_types = $_POST['indicateur_types'] ?? [];
error_log(print_r($_POST, true));
error_log(print_r($indicateur_results, true));
error_log(print_r($indicateur_types, true));
foreach ($indicateur_results as $i => $value) {
    $type = $indicateur_types[$i] ?? null;

    if ($type === 'boolean' && ($value === '' || $value === null)) {
        $indicateur_results[$i] = 0;
    }
}

// $indicateur_results = $_POST['indicateur_results'] ?? [];
// $indicateur_types = $_POST['indicateur_types'] ?? [];
// error_log("TEST: This should appear in the error log.");
// error_log('Raw POST data: ' . print_r($_POST, true));
// foreach ($indicateur_results as $i => $value) {
//     $type = $indicateur_types[$i] ?? null;
    
//     // Handle boolean types - convert empty string or null to 0
//     if ($type === 'boolean') {
//         error_log('this variable is boolean');
//         $indicateur_results[$i] = (empty($value) && $value !== '0') ? 0 : (int)$value;
//     }
//     // Handle non-boolean types - convert empty string to null
//     else {
//         error_log('this variable is not');
//         $indicateur_results[$i] = ($value === '') ? null : $value;
//     }
// }

require 'functions.php';

try {
    $pdo->beginTransaction();

    // Vérification des données POST
    if (empty($_POST)) {
        error_log("Aucune donnée POST reçue");
        $_SESSION['error'] = "Aucune donnée reçue. Veuillez remplir le formulaire.";
        header("Location: traitement_odd.php?id_odd=$id_odd");
        exit();
    }
    
    // Traitement des variables
    $debug_updates = [];
   foreach ($_POST as $field_name => $value) {
    // Ignorer les champs non variables
    if ($field_name === 'id_odd' || $field_name === 'indicateur_results') {
        continue;
    }

    // Vérifier si c'est un champ variable (format: CODE_VARIABLE_ID)
    if (preg_match('/^([a-zA-Z0-9_]+)_(\d+)$/', $field_name, $matches)) {
        $code_var = $matches[1];
        $var_id = $matches[2];

        // Convertir une chaîne vide en NULL
        $db_value = $value === '' ? null : $value;

        // Vérifier que la variable existe
        $stmtCheck = $pdo->prepare("SELECT id_variable, id_indicateur FROM variables WHERE code_variable = ? AND id_variable = ?");
        $stmtCheck->execute([$code_var, $var_id]);

        if ($row = $stmtCheck->fetch()) {
            $indicateur_id = $row['id_indicateur']; 

            $stmt = $pdo->prepare("
                INSERT INTO val_variables (id_variable, indicateur_id, id_user, annee, valeur_variable)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE valeur_variable = ?
            ");
            $stmt->execute([$var_id, $indicateur_id, $id_user, $annee, $db_value, $db_value]);
            
            // Stocker les informations pour le debugging
            $debug_updates[] = [
                'id_variable' => $var_id,
                'valeur_variable' => $db_value,
                'code_variable' => $code_var,
                'indicateur_id' => $indicateur_id
            ];
        }
    }
}

    // Récupération des indicateurs pour l'ODD
    $stmtIndicators = $pdo->prepare("
        SELECT i.id_indicateur, i.code_indicateur 
        FROM indicateur i
        JOIN cibles c ON i.id_cible = c.id_cible
        WHERE c.id_odd = ?
    ");
    $stmtIndicators->execute([$id_odd]);
    $allIndicators = $stmtIndicators->fetchAll(PDO::FETCH_ASSOC);

    // Traitement des résultats des indicateurs
    $indicatorProgress = [];

    foreach ($indicateur_results as $indicator_id => $result_value) {
        if ($result_value === null) continue;
        
        $stmt = $pdo->prepare("SELECT code_indicateur FROM indicateur WHERE id_indicateur = ?");
        $stmt->execute([$indicator_id]);
        if ($indicator = $stmt->fetch()) {
            $codeIndicateur = 'indicateur' . str_replace('.', '', $indicator['code_indicateur']);
            $indicatorProgress[$codeIndicateur] = $result_value;
        }
    }
    // Calcul de la progression
    foreach ($allIndicators as $indicator) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) as filled, 
                (SELECT COUNT(*) FROM variables WHERE id_indicateur = ?) as total
            FROM val_variables vv
            JOIN variables v ON vv.id_variable = v.id_variable
            WHERE v.id_indicateur = ? 
            AND vv.id_user = ? 
            AND vv.annee = ?
            AND vv.valeur_variable IS NOT NULL
        ");
        $stmtCheck->execute([$indicator['id_indicateur'], $indicator['id_indicateur'], $id_user, $annee]);
        $result = $stmtCheck->fetch();
        
        $progress = ($result['total'] > 0) ? round(($result['filled'] / $result['total']) * 100) : 0;
        $codeIndicateur = 'indicateur' . str_replace('.', '', $indicator['code_indicateur']);
        
        if (!isset($indicatorProgress[$codeIndicateur])) {
            $indicatorProgress[$codeIndicateur] = $progress;
        }
    }

    // Mise à jour de la table ODD
    $sanitizedValues = array_map(function($v) {
    // Vérifier si la valeur est vide ou si c'est une chaîne composée uniquement d'espaces.
    return (empty($v) || trim($v) === '') ? null : $v;
}, array_values($indicatorProgress));

    $sanitizedValues = array_merge($sanitizedValues, [$id_user, $annee]);
    

    $oddTable = "odd" . $id_odd;
    $columns = array_keys($indicatorProgress);
    $values = array_merge(array_values($indicatorProgress), [$id_user, $annee]);

    $sql = "
    INSERT INTO $oddTable 
    (" . implode(', ', $columns) . ", id_user, annee) 
    VALUES (" . rtrim(str_repeat('?, ', count($columns)), ', ') . ", ?, ?)
    ON DUPLICATE KEY UPDATE 
    " . implode(', ', array_map(function($col) { return "`$col` = VALUES(`$col`)"; }, $columns)) . ", 
    id_user = VALUES(id_user), 
    annee = VALUES(annee)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($sanitizedValues);

    // Mise à jour de la progression globale
    // Obtenir le nombre total d'indicateurs pour cet ODD
    $stmtTotalIndicators = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM indicateur i
        JOIN cibles c ON i.id_cible = c.id_cible
        WHERE c.id_odd = ?
    ");

    $stmtTotalIndicators->execute([$id_odd]);
    $totalIndicators = $stmtTotalIndicators->fetchColumn();

    // Calculer la progression
    $filled_indicators = array_filter($indicateur_results, function($value) { 
        return $value !== null; 
    });
    $sum_indicateur_results = array_sum($filled_indicators);
    $total_filled = count($filled_indicators);

    // Case 1:
    $progress = $totalIndicators > 0 ? round(($sum_indicateur_results / $totalIndicators)) : 0;
    // Endcase 1:
    // Case 2:
    // $filled_indicators_count = count(array_filter($indicateur_results, function($value) { 
    //     return $value !== null;
    // }));

    // $progress = $totalIndicators > 0 ? round(($filled_indicators_count / $totalIndicators) * 100) : 0;
    // Endcase 2:

    if (checkOddTraiteExists($pdo, $id_user, $annee, $id_odd)) {
        $stmtProgress = $pdo->prepare("UPDATE odd_traite SET odd{$id_odd} = ? WHERE id_user = ? AND annee = ?");
        $stmtProgress->execute([$progress, $id_user, $annee]);
    } else {
        $stmtProgress = $pdo->prepare("INSERT INTO odd_traite (id_user, id_ville, annee, odd{$id_odd}) VALUES (?, ?, ?, ?)");
        $stmtProgress->execute([$id_user, $id_ville, $annee, $progress]);
    }

     $completedCount = count($filled_indicators);
    //  Notifications
    if ($completedCount < $totalIndicators) {
        // Supprimer d'abord la notification existante concernant l'odd actuel
        // $stmtDelete = $pdo->prepare("DELETE FROM notifications 
        //                            WHERE id_user = ? ");
        // $stmtDelete->execute([$id_user]);
        
        // Insérer une nouvelle notification
        $stmtNotif = $pdo->prepare("INSERT INTO notifications 
                                  (id_user,  message, created_at) 
                                  VALUES (?,  ?, NOW())");
        $message = "Les indicateurs de l'ODD {$id_odd} ne sont pas tous complétés";
        $stmtNotif->execute([$id_user,  $message]);
    }

    $pdo->commit();

    // Affichage des alertes de débogage
    // if (!empty($debug_updates)) {
    //     echo "<script>";
    //     foreach ($debug_updates as $update) {
    //         $value = $update['valeur_variable'] === null ? 'NULL' : $update['valeur_variable'];
    //         echo "alert('Mise à jour variable:\\nID: " . $update['id_variable'] . "\\nCode: " . $update['code_variable'] . "\\nIndicateur: " . $update['indicateur_id'] . "\\nValeur: " . $value . "');";
    //     }
    //     echo "</script>";
        
    //     echo "<script>
    //         setTimeout(function() {
    //             window.location.href = 'traitement_odd.php?id_odd=$id_odd';
    //         }, " . (count($debug_updates) * 1500) . ");
    //     </script>";
    //     exit();
    // } else {
    //     $_SESSION['message'] = "Les données ont été enregistrées avec succès!";
    //     header("Location: traitement_odd.php?id_odd=$id_odd");
    //     exit();
    // }
   
     $_SESSION['message'] = "Les données ont été enregistrées avec succès!";
        header("Location: traitement_odd.php?id_odd=$id_odd");
        // header('Location: page1.php');
        exit();
} catch (Exception $e) {
    // Envoyer le message d'erreur

    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
    header("Location: traitement_odd.php?id_odd=$id_odd");
    // header('Location: page1.php');
    exit();

    // Afficher les erreurs lors des tests
    // $pdo->rollBack();
    // error_log("Erreur SQL : " . $e->getMessage());

    // echo "<h2>Une erreur est survenue :</h2>";
    // echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    // echo "<h3>Trace :</h3>";
    // echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
    // echo "<h3>Données envoyées :</h3>";
    // echo "<pre>" . print_r($_POST, true) . "</pre>";
    exit();
}