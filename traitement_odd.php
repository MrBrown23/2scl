<?php
// Connexion PDO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST = array();
}

$pdo = new PDO("mysql:host=localhost;dbname=odd_db;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

session_start();
$id_user = $_SESSION['id_user'] ?? 1;
$id_ville = $_SESSION['id_ville'] ?? 1;
$annee = date('Y');
// $id_odd = isset($_GET['id_odd']) ? (int)$_GET['id_odd'] : 1;
$id_odd = $_GET['id_odd'] ?? 1;
$areaCode = 250;



$stmtCibles = $pdo->prepare("SELECT * FROM cibles WHERE id_odd = :id_odd ORDER BY id_cible");
$stmtCibles->execute(['id_odd' => $id_odd]);
$cibles = $stmtCibles->fetchAll(PDO::FETCH_ASSOC);

// Prevouis page url FIXED

$previous = 'page1.php';

require 'functions.php';

// $stmUnits = $pdo->prepare("SELECT * FROM indicateurs WHERE code REGEXP '\\b?\\.[^}].[^}]'");
// $stmUnits->execute([$id_odd]);
// $units =  $stmUnits->fetchAll(PDO::FETCH_ASSOC);

$values = getAllIndicatorValuesForGoal($id_odd, $areaCode);
// foreach($values as $value){
//     echo $value['unite_mesure'];
// } 


$page_title = "ODD $id_odd - Cibles et Indicateurs";



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title ) ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #combined-chart {
            width: 100% !important;
            min-height: 300px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
    </style>
    <!-- Charts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2/dist/chartjs-plugin-annotation.min.js"></script>

    
</head>
<body class="bg-gray-50">
    <!-- ODD I Header -->
    <?php 
        
        include 'header.php';
    ?>
   
    <div class="bg-white mx-6 mt-6 p-6 rounded-xl shadow-sm max-w-4xl mx-auto px-4 py-6 overflow-hidden">
        
        
        <div class="flex items-center justify-between  mb-4">
            
            <a href="<?php echo htmlspecialchars($previous); ?>" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Accueil
            </a>
            <!-- <h2 class="text-lg font-semibold text-gray-800">Progression Globale</h2> -->
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                <?= date('d/m/Y') ?>
            </span>
        </div>
        <!-- <div class="h-3 bg-gray-200 rounded-full overflow-hidden mb-6">
            <div id="global-progress-bar" class="h-full bg-gradient-to-r from-blue-500 to-green-500" style="width: 0%"></div>
        </div> -->
        
        <!-- Graphique combiné -->
        <div class="mt-8">
            <h3 class="text-md font-medium text-gray-700 mb-4">Comparaison des indicateurs avec leurs seuils</h3>
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <canvas id="combined-chart" height="300"></canvas>
            </div>
        </div>
    </div>

        <form method="post" action="valider.php" class="max-w-4xl mx-auto px-4 py-6">
            <div class="space-y-4">
                <?php foreach ($cibles as $cible): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="accordion-header flex items-center p-4 cursor-pointer bg-blue-50 hover:bg-blue-100 transition-colors">
                            <img class="w-12 h-12 mr-4" src="images/cibles/<?= $cible['code_cible'] ?>.svg" alt="logo">
                            <h3 class="text-lg font-semibold text-gray-800 flex-1"> Cible (<?= $cible['code_cible'] ?>):<?= $cible['titre'] ?></h3>
                        </div>
                        <div class="accordion-content px-6 py-4 hidden">
                            <p class="text-gray-600 mb-4 whitespace-pre-line"><?= nl2br(htmlspecialchars($cible['description'])) ?></p>
                            
                            <?php foreach (getIndicateurs($pdo, $cible['id_cible']) as $indicateur): 
                                $variables = getVariables($pdo, $indicateur['id_indicateur'], $id_user, $annee);
                                $hasValues = false;
                                foreach ($variables as $var) {
                                   
                                    if ($var['valeur_variable'] !== null) {
                                        $hasValues = true;
                                        break;
                                    }
                                     
                                }
                                

                                // Récupérer le seuil pour cet indicateur
                                $stmtSeuil = $pdo->prepare("SELECT valeur_quantitative FROM seuil_indicateur WHERE id_indicateur = ?");
                                $stmtSeuil->execute([$indicateur['id_indicateur']]);
                                $seuilResult = $stmtSeuil->fetchColumn();
                                // $latestValue = getLatestValidValueFromApi($indicateur['code_indicateur'], $areaCode);
                                // $seuil = $latestValue ?? $seuilResult;
                                $seuil = $values['indicator'] ?? $seuilResult;
                                $type_valeur = $indicateur['type_valeur'];
                                $unite = $indicateur['unite_mesure'];
                                $normalized_type = strtolower(trim($type_valeur));
                                // Récupérer les données historiques
                                // $resultatFinal = calculerIndicateur($pdo, $indicateur['formule'], $indicateur['id_indicateur'], $id_user, $annee);
                                 $resultatFinal = calculerIndicateur($pdo, $indicateur['formule'], $indicateur['id_indicateur'], $id_user, $annee)
                                    ?? ($indicateur['type_valeur'] === 'Boolean' ? getBooleanFromOddTable(
                                            $pdo, 
                                            $id_odd, 
                                            $indicateur['code_indicateur'], 
                                            $id_user, 
                                            $annee
                                        )
                                        : null);
                                ?>
                            <div class="indicateur bg-gray-50 p-4 mb-4 rounded-lg border border-gray-200">
                                <div class="flex justify-between items-center mb-3">
                                    <!-- =========================================================================================== -->
                                    <h4 class="text-md font-medium text-blue-600"> Indicateur (<?=$indicateur['code_indicateur'] ?>):<?= htmlspecialchars($indicateur['name']) ?></h4>
                                    <button type="button" class="edit-toggle-btn px-3 py-1 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 transition-colors flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        <!-- Remplir to Saisir FIXED -->
                                        <span class="text-sm"><?= $hasValues ? 'Modifier' : 'Saisir' ?></span>
                                    </button>
                                </div>
            
                                <!-- Display View -->
                                <div class="display-view">
                                    <p class="text-sm font-medium text-gray-700">Résultat  : <span class="resultat text-gray-900 font-bold">- </span></p>
                                    <input type="hidden" name="indicateur_results[<?= $indicateur['id_indicateur'] ?>]" 
                                        class="result-input" value="">
                                        <input type="hidden" name="indicateur_types[<?= $indicateur['id_indicateur'] ?>]" value="<?= $normalized_type ?>">

                                    
                                    <!-- Histogramme avec seuil -->
                                    <?php if ($resultatFinal !== null): ?>
                                    <div class="mt-4">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">Valeur actuelle vs seuil (<?= $seuil ?> <?= htmlspecialchars($indicateur['unite_mesure']) ?>)</h5>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <canvas id="chart-<?= $indicateur['id_indicateur'] ?>" height="150"></canvas>
                                        </div>
                                    </div>

    
                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const ctx = document.getElementById('chart-<?= $indicateur['id_indicateur'] ?>');
                                        const seuil = <?= $seuil ?: 0 ?>;
                                        const currentResult = <?= $resultatFinal ?>;
                                        const y_title = '<?= $unite ?>';
                                        
                                        new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: ['<?= $annee ?>'],
                                                datasets: [
                                                    {
                                                        label: 'Résultat actuel ()',
                                                        data: [currentResult],
                                                        backgroundColor: currentResult >= seuil ? 'rgba(40, 167, 69, 0.7)' : 'rgba(220, 53, 69, 0.7)',
                                                        borderColor: currentResult >= seuil ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)',
                                                        borderWidth: 1
                                                    }
                                                ]
                                            },
                                            options: {
                                                responsive: true,
                                                scales: {
                                                    y: {
                                                        beginAtZero: true,
                                                        max: 100,
                                                        title: {
                                                            display: true,
                                                            text: y_title
                                                        }
                                                    },
                                                    x: {
                                                        title: {
                                                            display: true,
                                                            text: 'Année'
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function(context) {
                                                                const value = context.raw;
                                                                const diff = value - seuil;
                                                                const prefix = diff >= 0 ? '+' : '';
                                                                return [
                                                                    `Résultat: ${value}<?= $indicateur['unite_mesure'] ?>`,
                                                                    `Seuil: ${seuil}<?= $indicateur['unite_mesure'] ?>`,
                                                                    `Écart: ${prefix}${diff.toFixed(1)}<?= $indicateur['unite_mesure'] ?>`
                                                                ];
                                                            }
                                                        }
                                                    },
                                                    legend: {
                                                        position: 'bottom',
                                                        labels: {
                                                            boxWidth: 12
                                                        }
                                                    },
                                                    // Ajout d'une annotation pour le seuil
                                                    annotation: {
                                                        annotations: {
                                                            lineSeuil: {
                                                                type: 'line',
                                                                yMin: seuil,
                                                                yMax: seuil,
                                                                borderColor: 'rgba(0, 0, 0, 0.7)',
                                                                borderWidth: 2,
                                                                borderDash: [6, 6],
                                                                label: {
                                                                    content: `Seuil: ${seuil} <?= $indicateur['unite_mesure'] ?>`,
                                                                    enabled: true,
                                                                    position: 'right'
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    });
                                </script>
                <?php endif; ?>

            </div>
            
            <!-- Edit View -->
            <?php
                $normalized_type = strtolower(trim($type_valeur));

                switch ($normalized_type) {
                    case 'qualitatif':
                    case 'qualitative':
                        $input_type = 'text';
                        break;
                    case 'index':
                    case 'pourcentage':
                    case 'ratio':
                        $input_type = 'number';
                        break;
                    case 'boolean':
                        $input_type = 'checkbox';
                    default:
                        $input_type = 'number'; 
                        break;
                }
            ?>
            <div class="edit-view hidden " 
                data-formule="<?= htmlspecialchars($indicateur['formule']) ?>"
                data-unit="<?= htmlspecialchars($indicateur['unite_mesure']) ?>"
                data-input-type="<?= $input_type ?>"
                data-type="<?= $normalized_type ?>"
                >
                <?php 
                    if ($normalized_type === 'boolean'):
                        $booleanValue = getBooleanFromOddTable($pdo, $id_odd, $indicateur['code_indicateur'], $id_user, $annee);
                        $isChecked = ($booleanValue === 100);
                ?>
                <label class="inline-flex items-center space-x-2 mb-3">
                    <input type="checkbox"
                            class="form-checkbox h-5 w-5 text-blue-600 transition duration-150 ease-in-out"
                            name="boolean_var"
                            data-type="boolean"
                             <?= $isChecked ? 'checked' : '' ?>
                           >
                    
                </label>

                
                <?php else :
                     foreach ($variables as $var): ?>
                    
                    <label class="block text-sm font-medium text-gray-500 mb-1"><?= htmlspecialchars($var['nom_variable']) ?></label>
                    
                    <input type="<?= $input_type ?>" step="any" 
                        name="<?= $var['code_variable'] ?>_<?= $var['id_variable'] ?>"
                        value="<?= htmlspecialchars($var['valeur_variable'] ?? '') ?>"
                        data-id="<?= $var['id_variable'] ?>"
                        data-type="<?= $normalized_type ?>"
                        data_unit="<?= htmlspecialchars($indicateur['unite_mesure']) ?>"
                        min="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500
                            focus:border-blue-500 mb-3">
                            <p class='text-red-600'></p>
                <?php endforeach; ?>
                <?php endif; ?>
                <p class="text-sm font-medium text-gray-700">Résultat : 
                    <span class="resultatEdited text-gray-900 font-bold">
                        <?= htmlspecialchars($indicateur['unite_mesure'].'+' ?? '-') ?>
                    </span>
                </p>
                </div>
            </div>
           <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="id_odd" value="<?= $id_odd ?>">
            <button type="submit" id='submit-el' class="w-full md:w-auto mx-auto mt-8 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Valider les données
            </button>
        </form>

        <!-- Afficher le message de succès. -->
        <?php if (isset($_SESSION['message'])): ?>
        <div id="success-message" class="fixed top-4 right-4 max-w-sm bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= htmlspecialchars($_SESSION['message']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['message']); ?>
        <script>
            setTimeout(() => {
                document.getElementById('success-message').style.display = 'none';
            }, 5000);
        </script>
        <?php endif; ?>

        <!-- Afficher l'erreur -->
        <?php if (isset($_SESSION['error'])): ?>
            <div id="error-message" class="fixed top-4 right-4 max-w-sm bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
            <script>
                setTimeout(() => {
                    document.getElementById('error-message').style.display = 'none';
                }, 5000); 
            </script>
        <?php endif; ?>

    

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    content.classList.toggle('hidden');
                });
            });

            // Modifier la fonctionnalité de toggling
            document.querySelectorAll('.edit-toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const indicateur = e.currentTarget.closest('.indicateur');
                const editView = indicateur.querySelector('.edit-view');
                const displayView = indicateur.querySelector('.display-view');
                
                editView.classList.toggle('hidden');
                displayView.classList.toggle('hidden');
                
                // Obtenir l'état actuel
                const isEditing = !editView.classList.contains('hidden');
                const span = btn.querySelector('span');
                
                // Mise à jour du texte du bouton en fonction de l'état et de l'objectif initial
                if (isEditing) {
                    span.textContent = 'Fermer';
                } else {
                    // Vérifier si nous disposons de valeurs pour déterminer l'état d'origine
                    const inputs = editView.querySelectorAll('input');
                    let hasValues = false;
                    inputs.forEach(input => {
                        if (input.value !== '') hasValues = true;
                    });
                    span.textContent = hasValues ? 'Modifier' : 'Saisir';
                    // Remplir to Saisir FIXED AGAIN
                }
                
                // Mise à jour de l'icône
                const icon = btn.querySelector('svg');
                icon.innerHTML = isEditing ? 
                    '<path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />' :
                    '<path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />';
            });
        });

        // Calcul dynamique pour le display-view
        document.querySelectorAll('.edit-view').forEach((form, index) => {
            const inputs = form.querySelectorAll('input');
            const span = form.closest('.indicateur').querySelector('.resultat');
            const resultInput = form.closest('.indicateur').querySelector('.result-input');
            const type = form.dataset.type;
            

            function calculer() {
                if (type === 'boolean') {
                    const checkbox = form.querySelector('input[type="checkbox"]');
                    if (!checkbox) {
                        alert('No checkbox')    
                        return;
                    }
                    const result = checkbox.checked ? 100 : 0;
                    span.textContent = result + ' %';
                    if (resultInput) resultInput.value = result;
                    return;
                }
                let formule = form.dataset.formule;
                let unit = form.dataset.unit;
                let ok = true;
                inputs.forEach(input => {
                    if (!input.value) ok = false;
                    // Extraire le nom de la variable d'origine (avant le underscore)
                    const varName = input.name.split('_')[0];
                    formule = formule.replaceAll(`{${varName}}`, input.value || '0');
                });
                if (ok) {
                    try {
                        const res = eval(formule);
                        
                        
                        const formattedRes = isNaN(res) ? '-' : res.toFixed(2) + ' ' + unit;
                        span.textContent = formattedRes;
                        if (resultInput) {
                            resultInput.value = isNaN(res) ? '' : res.toFixed(2);
                        }
                    } catch (e) {
                        span.textContent = 'Erreur';
                        if (resultInput) resultInput.value = '';
                    }
                } else {
                    span.textContent = '-';
                    if (resultInput) resultInput.value = '';
                }
            }

            inputs.forEach(input => input.addEventListener('input', calculer));
            calculer(); // Calcul initial
            });

            // Calcul dynamique pour le edit-view
            document.querySelectorAll('.edit-view').forEach((form, index) => {
                const inputs = form.querySelectorAll('input');
                const span = form.querySelector('.resultatEdited');
                const resultInput = form.closest('.indicateur').querySelector('.result-input');
                const submitEl =  document.getElementById('submit-el');

                // Function to check the input for validity
                function validateInput(input) {
                    const value = parseFloat(input.value);
                    const type = input.dataset.type; 
                    const errorMessage = input.nextElementSibling; // The error message will be the next sibling element

                    // Check if the value is less than or equal to 0
                    if (isNaN(value) || value < 0.01 || !isFinite(value)) {
                        if (errorMessage) {
                            errorMessage.textContent = 'Veuillez entrer une valeur supérieure à zéro et valide!';
                            errorMessage.classList.remove('hidden'); // Make sure the error message is visible
                            // submitEl.disabled = true;
                        }
                        input.classList.add('border-red-500'); // Add red border to the input
                        return false;
                    }
                    // else if (type === 'pourcentage' && (value > 100)) {
                    //     if (errorMessage) {
                    //         errorMessage.textContent = 'Veuillez entrer une valeur entre 0 et 100!';
                    //         errorMessage.classList.remove('hidden'); // Make sure the error message is visible
                    //     }
                    //     input.classList.add('border-red-500'); // Add red border to the input
                    //     return false;
                    // }
                    else {
                        // Hide the error message and reset input border if valid
                        if (errorMessage) {
                            errorMessage.textContent = ''; // Clear the error message
                            errorMessage.classList.add('hidden');
                        }
                        input.classList.remove('border-red-500'); // Remove red border if the value is valid
                        return true;
                    }
                }

                // Function to calculate the result
                function calculer() {
                    let formule = form.dataset.formule;
                    let unit = form.dataset.unit;
                    let ok = true;
                    const type = form.dataset.type;
                     if (type === 'boolean') {
                        const checkbox = form.querySelector('input[type="checkbox"]');
                        if (!checkbox) return;
                        const result = checkbox.checked ? 100 : 0;
                        span.textContent = result + ' %';
                        if (resultInput) resultInput.value = result;
                        return; 
                    }

                   
                    // Valider chaque entrée et vérifier si elle n'est pas invalide
                    inputs.forEach(input => {
                        let value;

                        if (input.type === 'checkbox') {
                            value = input.checked ? 1 : 0; // Checkboxes return 1 or 0
                        } else {
                            if (!input.value || !validateInput(input)) {
                                ok = false;
                            }
                            value = input.value || '0';
                        }
                        // Extraire le nom de la variable d'origine (avant le underscore)
                        const varName = input.name.split('_')[0];
                         if (varName && formule) {
                            formule = formule.replaceAll(`{${varName}}`, value);
                        }
                        
                    });

                if (ok && formule) {
                    try {
                        const res = eval(formule);
                        const formattedRes = isNaN(res) ? '-' : res.toFixed(2) + ' ' + unit;
                        const type = form.dataset.type;
                        // const unit = form.dataset.unit;
                        if (type === 'boolean') {
                            const checkbox = form.querySelector('input[type="checkbox"]');
                            if (!checkbox) return;
                            const result = checkbox.checked ? 100 : 0;
                            span.textContent = result + ' %';
                            if (resultInput) resultInput.value = result;
                            return;
                        }
                        if (type === 'pourcentage' && (res < 0 || res > 100)) {
                            span.textContent = 'Résultat hors limites (0 - 100%) pour un indicateur de pourcentage';
                            span.classList.add('text-red-500'); 
                            submitEl.disabled = true;
                            if (resultInput) resultInput.value = '';
                            return;
                        }
                        if(unit === 'par 1M' && (res < 0 || res > 100000)){
                            span.textContent = 'Résultat hors limites (0 - 1000000)';
                            span.classList.add('text-red-500'); 
                            submitEl.disabled = true;
                            if (resultInput) resultInput.value = '';
                            return;
                        }
                        if(unit === 'par 100k' && (res < 0 || res > 100000)){
                            span.textContent = 'Résultat hors limites (0 - 100000)';
                            span.classList.add('text-red-500'); 
                            submitEl.disabled = true;
                            if (resultInput) resultInput.value = '';
                            return;
                        }
                        if(unit === 'par 1k' && (res < 0 || res > 100000)){
                            span.textContent = 'Résultat hors limites (0 - 10000)';
                            span.classList.add('text-red-500'); 
                            submitEl.disabled = true;
                            if (resultInput) resultInput.value = '';
                            return;
                        }
                        else {
                            span.classList.remove('text-red-500');
                            submitEl.disabled = false;
                        }
                        span.textContent = formattedRes;
                        if (resultInput) {
                            resultInput.value = isNaN(res) ? '' : res.toFixed(2);
                        }
                    } catch (e) {
                        span.textContent = 'Erreur';
                        if (resultInput) resultInput.value = '';
                    }
                } else {
                    span.textContent = '-';
                    if (resultInput) resultInput.value = '';
                }
        }

        // Listen for input changes
        inputs.forEach(input => {
            input.addEventListener('input', calculer);
            if (input.type === 'checkbox') {
                input.addEventListener('change', calculer);
            }
        });
        
        // Initial calculation when the page loads
        calculer(); // Calcul initial
        });

        });

    </script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Préparer les données pour le graphique combiné
        const indicateursData = [
            <?php 
                $combinedData = [];
                foreach ($cibles as $cible): 
                    foreach (getIndicateurs($pdo, $cible['id_cible']) as $indicateur):
                        //  $column_name = $indicateur['code_indicateur'];
                        //     echo "<script>alert('Column $column_name does not exist in table $table_name');</script>";
                        $resultatFinal = calculerIndicateur($pdo, $indicateur['formule'], $indicateur['id_indicateur'], $id_user, $annee)
                        ?? ($indicateur['type_valeur'] === 'Boolean' ? getBooleanFromOddTable(
                                $pdo, 
                                $id_odd, 
                                $indicateur['code_indicateur'], 
                                $id_user, 
                                $annee
                            )
                            : null);
                           
                        $normalized_value = 0;
                         
                        if ($resultatFinal !== null) {
                            // Obtenir l'ordre dans le tableau des variables
                            $ordre = getOrderFromVariable($pdo, $indicateur['id_indicateur']);
                            
                            if ($ordre !== null) {
                                $stmtSeuil = $pdo->prepare("SELECT valeur_quantitative FROM seuil_indicateur WHERE id_indicateur = ?");
                                $stmtSeuil->execute([$indicateur['id_indicateur']]);
                                $seuilResult = $stmtSeuil->fetchColumn();
                                // $latestValue = getLatestValidValueFromApi($indicateur['code_indicateur'], $areaCode);
                                // $seuil = $latestValue ?? $seuilResult;
                                $seuil = $values['indicator'] ?? $seuilResult;
                                
                                $labelTemp = "Indicateur (" . addslashes($indicateur['code_indicateur']) . ")";
                                $type_valeur = $indicateur['type_valeur'];
                                $unite = $indicateur['unite_mesure'];
                                switch($type_valeur){
                                    case 'Ratio':
                                        // $normalized_value = $resultatFinal * 100;
                                        $normalized_value = $resultatFinal;
                                        break;
                                    case 'Quantitatif':
                                        $table_name = "odd" . $id_odd;
                                        $excluded = ['id', 'id_user', 'annee'];

                                        // try {
                                        //     // Step 1: Get all column names
                                        //     $stmtColumns = $pdo->prepare("SHOW COLUMNS FROM `$table_name`");
                                        //     $stmtColumns->execute();
                                        //     $columns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);

                                        //     if (!$columns) {
                                        //         error_log("Failed to fetch columns from table $table_name.");
                                        //         break;
                                        //     }

                                        //     // Step 2: Filter out excluded columns
                                        //     $indicatorColumns = array_filter($columns, fn($col) => !in_array($col, $excluded));

                                        //     // Step 3: Build MIN/MAX parts
                                        //     $minMaxParts = [];
                                        //     foreach ($indicatorColumns as $col) {
                                        //         $minMaxParts[] = "MIN(`$col`) AS min_$col";
                                        //         $minMaxParts[] = "MAX(`$col`) AS max_$col";
                                        //     }

                                        //     // Step 4: Combine into one SELECT
                                        //     $selectClause = implode(", ", $minMaxParts);
                                        //     $sql = "SELECT $selectClause FROM `$table_name` WHERE id_user = ? AND annee = ?";
                                            
                                        //     // Step 5: Prepare and execute
                                        //     $stmtMinMax = $pdo->prepare($sql);
                                        //     $stmtMinMax->execute([$id_user, $annee]);
                                        //     $minMaxValues = $stmtMinMax->fetch(PDO::FETCH_ASSOC);

                                        //     if (!$minMaxValues) {
                                        //         error_log("No min/max values returned from $table_name for user $id_user and year $annee.");
                                        //         $normalized_value = 100;
                                        //         break;
                                        //     }

                                        //     // Get the correct column name for this indicator
                                        //     $col = $indicateur['code_indicateur'];
                                        //     $minKey = "min_$col";
                                        //     $maxKey = "max_$col";

                                        //     if (isset($minMaxValues[$minKey], $minMaxValues[$maxKey]) &&
                                        //         $minMaxValues[$minKey] !== null && $minMaxValues[$maxKey] !== null) {
                                                
                                        //         $min = $minMaxValues[$minKey];
                                        //         $max = $minMaxValues[$maxKey];

                                        //         if ($max != $min) {
                                        //             $normalized_value = (($resultatFinal - $min) / ($max - $min)) * 100;
                                        //         } else {
                                        //             error_log("Min and max are equal for $col (value: $min). Avoiding division by zero.");
                                        //             $normalized_value = 100;
                                        //         }

                                        //     } else {
                                        //         error_log("Missing min or max for column $col: " . json_encode($minMaxValues));
                                        //         $normalized_value = 100;
                                        //     }

                                        // } catch (PDOException $e) {
                                        //     error_log("PDOException while processing Quantitatif normalization for $col: " . $e->getMessage());
                                        //     $normalized_value = 100;
                                        // }
                                        $normalized_value = $resultatFinal;
                                        break;

                                    
                                        default :
                                        $normalized_value = $resultatFinal;
                                }
                                echo '{
                                    label: "' . addslashes($labelTemp) . '",
                                    normalizedValue: ' . $normalized_value . ',
                                    value: ' . $resultatFinal . ',
                                    seuil: ' . $seuil . ',
                                    unite: "' . addslashes($unite) . '",
                                    type: "' . addslashes($type_valeur) . '",
                                    color: "' . ($normalized_value >= $seuil ? 'rgba(40, 167, 69, 0.7)' : 'rgba(220, 53, 69, 0.7)') . '",
                                    borderColor: "' . ($normalized_value >= $seuil ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)') . '"
                                },';
                            } else {
                                echo "No ordre found for indicator {$indicateur['id_indicateur']}<br>";
                            }
                        }
                    endforeach;
                endforeach;
            ?>

        ];
        

        // Créer le graphique combiné
        const ctx = document.getElementById('combined-chart');
        const labels = indicateursData.map(item => item.label);
        const values = indicateursData.map(item => item.value);
        const normalizedValues = indicateursData.map(item => item.normalizedValue); 
        const seuils = indicateursData.map(item => item.seuil);
        const backgroundColors = indicateursData.map(item => item.color);
        const borderColors = indicateursData.map(item => item.borderColor);
        const units = indicateursData.map(item => item.unite);
        const types = indicateursData.map(item => item.type);
        const y_title = 'Valeurs Normalisées';

        new Chart(ctx, {
            type: 'bar',
            data: {
               
                labels: labels,
                    datasets: [
                        {
                            label: `Résultats actuels`,
                            data: normalizedValues,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                            borderWidth: 1,
                            barPercentage: 0.7,
                            categoryPercentage: 0.7,
                            grouped: false
                        }
                        ,
    
                            {
                                label: 'Seuil',
                                data: seuils,
                                backgroundColor: 'rgba(100, 100, 100, 0.3)',
                                borderColor: 'rgba(100, 100, 100, 1)',
                                borderWidth: 1,
                                barPercentage: 0.9,
                                categoryPercentage: 0.9,
                                grouped: false
                            }
                    ]
                },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: y_title // !TODO: I'll change that later to??
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const index = context.dataIndex;
                                const value = indicateursData[index].value;
                                const normalizedValue = indicateursData[index].normalizedValue;
                                const seuil = indicateursData[index].seuil;
                                const unite = indicateursData[index].unite;
                                
                                if (context.datasetIndex === 0) {
                                    const diff = normalizedValue - indicateursData[index].seuil;
                                    const prefix = diff >= 0 ? '+' : '';
                                    return [
                                        `${label}: ${value} ${unite}`,
                                        `Seuil: ${seuil} ${unite}`,
                                        `Écart: ${prefix}${diff.toFixed(1)} ${unite}`
                                    ];
                                } else {
                                    return `${label}: ${seuil} ${unite}`;
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    });
</script>

</body>
</html>
