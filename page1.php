<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Connexion à la base de données
$pdo = new PDO("mysql:host=localhost;dbname=odd_db;charset=utf8", "root", "");

// Paramètres utilisateur
$id_user = 1;
$id_ville = 1;
$annee = 2025;
$_SESSION['id_user'] = $id_user;
$_SESSION['id_ville'] = $id_ville;
$_SESSION['annee'] = $annee;

// Initialiser un tableau pour stocker les ODD
$odds = [];

// Récupération des métadonnées des ODD
$req_meta = $pdo->query("SELECT * FROM odd ORDER BY id_odd");
$metas = $req_meta->fetchAll(PDO::FETCH_ASSOC);

// Récupération des pourcentages
$req_pourcents = $pdo->prepare("SELECT * FROM odd_traite WHERE id_user = ? AND id_ville = ? AND annee = ?");
$req_pourcents->execute([$id_user, $id_ville, $annee]);
$traite = $req_pourcents->fetch(PDO::FETCH_ASSOC);
$global_seuil = 0;




// Pour chaque ODD (de 1 à 17)
for ($i = 1; $i <= 17; $i++) {
    $meta = $metas[$i - 1];
    $table = "odd" . $i;

    // Calcul du nombre de réponses
    $req_indics = $pdo->prepare("SELECT * FROM $table WHERE id_user = ? AND annee = ?");
    $req_indics->execute([$id_user, $annee]);
    $row = $req_indics->fetch(PDO::FETCH_ASSOC);

    $nb_reponses = 0;
    $total_questions = 0;
    if ($row) {
        foreach ($row as $key => $val) {
            if (!in_array($key, ['id', 'id_user', 'annee']) && $key !== 'id_ville') {
                $total_questions++;
                if (!is_null($val) && trim($val) !== '') {
                    $nb_reponses++;
                }
            }
        }
    }

        // Obtenir un seuil pour cet ODD
        $req_seuil = $pdo->prepare("SELECT valeur_quantitative FROM seuil_odd WHERE id_odd = ? LIMIT 1");
        $req_seuil->execute([$i]);
        $seuil_data = $req_seuil->fetch(PDO::FETCH_ASSOC);
        $seuil = $seuil_data ? floatval($seuil_data['valeur_quantitative']) : null;
        $global_seuil += $seuil;

    // Ajouter les données à l'array
    $odds[] = [
        "id_odd" => $i,
        "titre" => $meta['titre'],
        "stitre" => $meta['stitre'],
        "coul" => $meta['coul'],
        "quest" => $meta['nb_indicateurs'],
        "pourcentage" => isset($traite["odd$i"]) ? $traite["odd$i"] : 0,
        "reponses" => $nb_reponses,
        "total" => $total_questions,
        "seuil" => $seuil
    ];
    
}

    $global_seuil /= 17;
    $page_title = "Tableau de Bord ODD";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <!-- Charts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2/dist/chartjs-plugin-annotation.min.js"></script>

</head>
<body class="bg-gray-50">
    <!-- Tableau de bord Header -->
    <?php 
        
        include 'header.php';
    ?>
   
<!-- Résumé de l'état d'avancement -->
<div class="bg-white mx-6 mt-6 p-6 rounded-xl shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Progression Globale</h2>
        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
            <?= date('d/m/Y') ?>
        </span>
    </div>

    <div class="h-3 bg-gray-200 rounded-full overflow-hidden relative">
        <div id="global-progress-bar" class="h-full bg-gradient-to-r from-blue-500 to-green-500 transition-all duration-500" style="width: 0%"></div>
        <!-- <div id="global-threshold-line" class="absolute top-0 bottom-0 w-0.5 bg-black opacity-70" style="left: <?= $global_seuil ?>%"></div> -->
    </div>

    <!-- <div class="mt-4">
        <button id="toggle-progress-view" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Afficher par rapport au seuil
        </button>
    </div> -->
    <!-- Graphique combiné des ODD -->
<div class="mt-8">
    <h3 class="text-md font-medium text-gray-700 mb-4">comparaison des valeurs des indicateurs calculés avec leurs seuils</h3>
    <div class="bg-white p-4 rounded-lg border border-gray-200">
        <canvas id="odd-combined-chart" height="300"></canvas>
    </div>
</div>
</div>



    <!-- ODD Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 p-6">
        <?php foreach ($odds as $odd): ?>
            <?php 
                $percentage = round($odd['reponses'] * 100 / $odd['quest'], 1);
                $valeur_relative = ($odd['seuil'] && $odd['seuil'] != 0) 
                    ? round(($percentage * 100) / $odd['seuil'], 1) 
                    : null;
                
                //  ($total_questions > 0) ? round(($nb_reponses * 100) / $total_questions, 1) : 0;

            ?>

        <div class="flex flex-col rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 h-full" data-percentage="<?= $odd['pourcentage'] ?>">
            <!-- Colored Header Section à un hauteur fixe -->
            <div class="odd-bg-<?= $odd['id_odd'] ?> w-full h-full flex flex-col justify-between">
               
                    <img class="h-full" src="images/odd/odd<?= $odd['id_odd'] ?>.svg" alt="ODD <?= $odd['id_odd'] ?>">
                
            </div>

            <div class="bg-white flex-1 flex flex-col">
                <!-- L'état d'avancement -->
                <div class="px-4 pt-3 pb-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span 
                            class="font-medium <?= $percentage >= 50 ? 'text-green-600' : ($percentage >= 25 ? 'text-orange-500' : 'text-red-500') ?>" 
                            data-percentage-text 
                            data-actual="<?= $percentage ?>" 
                            data-relative="<?= $valeur_relative ?>"
                        >
                            <?= $percentage ?>%
                        </span>
                       
                        <span class="text-gray-600"><?= $odd['reponses'] ?>/<?= $odd['quest'] ?></span>
                    </div>
                    <div class="relative h-4 bg-gray-200 rounded-full overflow-hidden">
                        <!-- Main progress bar -->
                        <div class="h-full <?= $percentage >= 50 ? 'bg-green-500' : ($percentage >= 25 ? 'bg-orange-400' : 'bg-red-400') ?>" 
                            style="width: <?= $percentage ?>%" 
                            data-actual="<?= $percentage ?>" 
                            data-relative="<?= $valeur_relative ?>" 
                            data-progress></div>

                        <!-- Ligne de seuil verticale -->
                       
                    </div>
                </div>

                <!-- Toggle Button -->
                    <!-- <div class="px-4 pb-2">
                        <button type="button" class="toggle-valeur w-full text-xs text-blue-600 hover:text-blue-800">
                            Afficher la valeur relative au seuil
                        </button>
                    </div> -->
                <!-- Bouton d'action -->
                <div class="px-4 pb-4 mt-auto">
                    
                    <form method="get" action="traitement_odd.php">
                        <input type="hidden" name="id_odd" value="<?= $odd['id_odd'] ?>">
                        <button class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white <?= $odd['reponses'] > 0 ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <?php if ($odd['reponses'] > 0): ?>
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                </svg>
                                CONTINUER
                            <?php else: ?>
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                </svg>
                                COMMENCER
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
                

            </div>
        </div>
        <?php endforeach; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const globalProgressBar = document.getElementById('global-progress-bar');
    const globalThresholdLine = document.getElementById('global-threshold-line');
    const toggleButton = document.getElementById('toggle-progress-view');
    
    // Calcul de la progression moyenne
    const progressCards = document.querySelectorAll('[data-percentage]');
    let totalProgress = 0;
    let validCards = 0;
    
    progressCards.forEach(card => {
        const progress = parseFloat(card.dataset.percentage);
        if (!isNaN(progress)) {
            totalProgress += progress;
            validCards++;
        }
    });

    
     
    
    const averageProgress = validCards > 0 ? Math.round(totalProgress / validCards) : 0;
    const thresholdValue = <?= $global_seuil ?>;
    
    // Initialisation
    let showRelativeProgress = false;
    updateProgressView();
    
    // Fonction pour mettre à jour l'affichage
    function updateProgressView() {
        if (showRelativeProgress) {
            // Mode relatif au seuil
            const relativeProgress = Math.min(100, Math.round((averageProgress / thresholdValue) * 100));
            globalProgressBar.style.width = `${relativeProgress}%`;
            globalProgressBar.className = 'h-full bg-gradient-to-r ' + 
                        (averageProgress >= 50 ? 'from-blue-500 to-green-500' : 
                        averageProgress >= 25 ? 'from-yellow-400 to-orange-500' : 'from-red-500 to-pink-500') + ' transition-all duration-500';
            globalThresholdLine.style.display = 'none';
            toggleButton.textContent = 'Afficher la progression absolue';
        } else {
            // Mode absolu
            globalProgressBar.style.width = `${averageProgress}%`;
            globalProgressBar.className = 'h-full bg-gradient-to-r ' + 
                        (averageProgress >= 50 ? 'from-blue-500 to-green-500' : 
                        averageProgress >= 25 ? 'from-yellow-400 to-orange-500' : 'from-red-500 to-pink-500');
            globalThresholdLine.style.display = 'block';
            toggleButton.textContent = 'Afficher par rapport au seuil';
        }
    }
    
    // Gestionnaire d'événement pour le bouton
    toggleButton.addEventListener('click', function() {
        showRelativeProgress = !showRelativeProgress;
        updateProgressView();
    });

    
    // Fonctions utilitaires
    function getProgressBarClass(value) {
        return value >= 50 ? 'h-full bg-green-500' :
               value >= 25 ? 'h-full bg-orange-400' :
               'h-full bg-red-400';
    }
    
    function getTextClass(value) {
        return value >= 50 ? 'font-medium text-green-600' :
               value >= 25 ? 'font-medium text-orange-500' :
               'font-medium text-red-500';
    }
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Préparer les données pour le graphique des ODD
        const oddData = [
                <?php 
                    foreach ($odds as $odd): 
                        if ($odd['seuil'] !== null):
                ?>
                {
                    label: "ODD <?= $odd['id_odd'] ?>",
                    code: "<?= $odd['id_odd'] ?>",
                    value: <?= $odd['pourcentage'] ?>,
                    seuil: <?= $odd['seuil'] ?>,
                    color: "<?= ($odd['pourcentage'] >= $odd['seuil']) ? 'rgba(40, 167, 69, 0.7)' : 'rgba(220, 53, 69, 0.7)' ?>",
                    borderColor: "<?= ($odd['pourcentage'] >= $odd['seuil']) ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)' ?>"
                },
                <?php 
                        endif;
                    endforeach; 
                ?>
        ];

        // Créer le graphique combiné des ODD
        const oddCtx = document.getElementById('odd-combined-chart');
        if (oddCtx && oddData.length > 0) {
            new Chart(oddCtx, {
                type: 'bar',
                data: {
                    labels: oddData.map(item => item.label),
                          datasets: [
    {
        label: 'Seuil (%)',
        data: oddData.map(item => item.seuil),
        backgroundColor: 'rgba(100, 100, 100, 0.3)',
        borderColor: 'rgba(100, 100, 100, 1)',
        borderWidth: 1,
        barPercentage: 0.9,
        categoryPercentage: 0.9,
        grouped: false
    },
    {
        label: 'Progrès actuel (%)',
        data: oddData.map(item => item.value),
        backgroundColor: oddData.map(item => item.color),
        borderColor: oddData.map(item => item.borderColor),
        borderWidth: 1,
        barPercentage: 0.7,
        categoryPercentage: 0.7,
        grouped: false
    }
]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Pourcentage'
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
                                    const data = oddData[context.dataIndex];
                                    if (context.dataset.label === 'Seuil (%)') {
                                        return `Seuil: ${context.raw}%`;
                                    }
                                    const diff = context.raw - data.seuil;
                                    const prefix = diff >= 0 ? '+' : '';
                                    return [
                                        `ODD ${data.code}: ${context.raw}%`,
                                        `Seuil: ${data.seuil}%`,
                                        `Écart: ${prefix}${diff.toFixed(1)}%`
                                    ];
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
        } else if (oddCtx) {
            oddCtx.parentElement.innerHTML = '<p class="text-gray-500 text-center py-4">Aucune donnée disponible pour les ODD</p>';
        }
    });
</script>
</body>
</html>