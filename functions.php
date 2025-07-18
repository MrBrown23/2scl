<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

    function checkOddTraiteExists(PDO $pdo, int $id_user, int $annee, int $id_odd): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM odd_traite 
                          WHERE id_user = ? AND annee = ?");
    $stmt->execute([$id_user, $annee]);
    return (bool)$stmt->fetch();
}

function calculerIndicateur($pdo, $formule, $id_indicateur, $id_user, $annee) {
    $stmt = $pdo->prepare("SELECT v.code_variable, vv.valeur_variable 
        FROM variables v 
        JOIN val_variables vv ON vv.id_variable = v.id_variable 
        WHERE v.id_indicateur = ? AND vv.id_user = ? AND vv.annee = ?");
    $stmt->execute([$id_indicateur, $id_user, $annee]);
    $variables = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 

    foreach ($variables as $code => $valeur) {
        $formule = str_replace("{{$code}}", $valeur ?? 0, $formule);
    }

    try {
        eval('$res = ' . $formule . ';');
        return is_numeric($res) ? round($res, 2) : null;
    } catch (Throwable $e) {
        
        return null;
    }
}

function getBooleanFromOddTable($pdo, $odd_id, $indicateur_code, $user_id, $annee) {

    $column_suffix = str_replace('.', '', $indicateur_code);
    $column_name = 'indicateur' . $column_suffix;
    

    $table_name = 'odd' . (int)$odd_id;
    
    try {
       
        // Récupérer la valeur
        $stmt = $pdo->prepare("SELECT $column_name 
                              FROM $table_name 
                              WHERE id_user = ? AND annee = ?");
        $stmt->execute([$user_id, $annee]);
        $value = $stmt->fetchColumn();
        error_log("Missing column: $column_name in table $table_name");
        
        // Retourne 100 pour vrai, 0 pour faux, null s'il n'y a pas de valeur
        return $value !== false ? $value : null;
        
    } catch (PDOException $e) {
        $errorMsg = "Error accessing $table_name in $column_name: " . $e->getMessage();
        // echo "<script>alert('$errorMsg');</script>";
        error_log($errorMsg);
        return null;
    }
}


function getOrderFromVariable(PDO $pdo, int $id_indicateur): ?int {
    $stmt = $pdo->prepare("SELECT ordre FROM variables WHERE id_variable = :id_indicateur");
    $stmt->execute(['id_indicateur' => $id_indicateur]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (int)$result['ordre'] : null; 
}



    // Fonctions supplémentaires pour les tests


    function getIndicateurs(PDO $pdo, int $id_cible): array {
        $stmt = $pdo->prepare("SELECT i.*, ind.formule, ind.type_valeur, ind.unite_mesure FROM indicateur i
                            LEFT JOIN indicateurs ind ON i.code_indicateur = ind.code
                            WHERE i.id_cible = :id_cible ORDER BY i.id_indicateur");
        $stmt->execute(['id_cible' => $id_cible]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getVariables(PDO $pdo, int $indicateur_id, int $id_user, string $annee): array {
        $stmt = $pdo->prepare("SELECT v.*, vv.valeur_variable FROM variables v
            LEFT JOIN val_variables vv ON v.id_variable = vv.id_variable AND vv.id_user = :id_user AND vv.annee = :annee
            WHERE v.id_indicateur = :id_indicateur");
        $stmt->execute([
            'id_indicateur' => $indicateur_id,
            'id_user' => $id_user,
            'annee' => $annee
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // Fonction permettant de récupérer et de renvoyer la dernière valeur valide ou null "option 2"
    function getLatestValidValueFromApi($indicator, $areaCode) {
        // API URL
        $apiUrl = "https://unstats.un.org/sdgapi/v1/sdg/Indicator/Data?indicator={$indicator}&areaCode={$areaCode}";

        // Récupérer les données à partir de l'API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Vérifiez si la réponse est a échoué
        if ($response === false) {
            return null; 
        }

        // Décoder la réponse JSON
        $data = json_decode($response, true);

        // Vérifiez si les données contiennent le champ « données »
        if (!isset($data['data'])) {
            return null; 
        }

        $currentYear = date("Y");

        // Trier les données par 'timePeriodStart' par ordre décroissant
        usort($data['data'], function($a, $b) {
            return $b['timePeriodStart'] - $a['timePeriodStart'];
        });

        // Parcourez les données et renvoyez la première valeur valide
        foreach ($data['data'] as $entry) {
            if ($entry['value'] !== null && $entry['timePeriodStart'] <= $currentYear) {
                return $entry['value'];
            }
        }

        return null; 
    }

    // Fonction pour l'option 1
    function getAllIndicatorValuesForGoal($goal, $areaCode) {
        $apiUrl = "https://unstats.un.org/sdgapi/v1/sdg/Goal/Data?goal={$goal}&areaCode={$areaCode}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        
        if (!isset($data['data']) || empty($data['data'])) {
            return null;
        }

        $currentYear = date("Y");
        $indicatorValues = [];

        foreach ($data['data'] as $entry) {
            if (!isset($entry['indicator'][0])) {
                continue;
            }
            
            $indicator = $entry['indicator'][0];
            $timePeriod = $entry['timePeriodStart'] ?? null;
            $value = $entry['value'] ?? null;

            // Ignorer si nous avons déjà une valeur pour cet indicateur
            if (isset($indicatorValues[$indicator])) {
                continue;
            }

            if ($value !== null && $timePeriod !== null && $timePeriod <= $currentYear) {
                $indicatorValues[$indicator] = $value;
            }
        }

        return $indicatorValues;
    }

?>