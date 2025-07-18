<?php 
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $pdo = new PDO('mysql::host=localhost;dbname=odd_db;charset=utf8', 'root', '');
    $id_user = 1;
    $id_ville = 1;
    $annee = date('Y');
    $_SESSION['id_user'] = $id_user;
    $_SESSION['id_ville'] = $id_ville;
    $_SESSION['annee'] = $annee;


    $odds = [];

    $req_query = $pdo->query('SELECT * FROM odd');
    $odds = $req_query->fetchAll(PDO::FETCH_ASSOC);
    $odd_scores = [];
    $query = $pdo->query('SELECT * FROM odd_traite');
    $odd_scores = $query->fetchAll(PDO::FETCH_ASSOC);

    $seuil_odd = [];
    $query = $pdo->query('SELECT * FROM seuil_odd');
    $seuil_odd = $query->fetchAll(PDO::FETCH_ASSOC);

    $reached_num = 0;
    $inprogress_num = 0;

    foreach ($odds as $i => $odd){
        $key = 'odd'.($i+1);
        $threshhold = $seuil_odd[$i]["valeur_quantitative"];
       
        $odds[$i]['score'] = $odd_scores[0][$key];
        $odds[$i]['threshhold'] = $threshhold;
        if($odds[$i]['score'] >= $odds[$i]['threshhold']){
          $odds[$i]['status'] = 'Atteint';
          $reached_num++;
        }
        else{
          $odds[$i]['status'] = 'En cours';
          $inprogress_num++;
        }
    }

    function getCibles($pdo, $index){
        try {
           $cibles = [];
            $query = $pdo->prepare('SELECT * FROM cibles WHERE');
            $query->execute([$index]);
            $cibles = $query->fetchAll(PDO::FETCH_ASSOC);
        return $cibles;
        } catch (\Throwable $th) {
            echo $th;
        }
        
    }

    $query = $pdo->query('SELECT COUNT(*) FROM notifications');
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    $alert = $result[0]["COUNT(*)"];

    $cibles = [];
    $query = $pdo->query('SELECT * FROM cibles');
    $cibles = $query->fetchAll(PDO::FETCH_ASSOC);


    $indicateurs = [];
    $query = $pdo->query('SELECT i.*, ind.formule, ind.type_valeur 
                        FROM indicateur i
                        JOIN indicateurs ind ON i.id_indicateur = ind.id_indicateur'
                        );
    $indicateurs = $query->fetchAll(PDO::FETCH_ASSOC);
 
    

    require 'functions.php';

    foreach($indicateurs as &$indicateur){
      $id_odd = $indicateur['code_indicateur'].str_split('.')[0] ;
      $resultatFinal = calculerIndicateur($pdo, $indicateur['formule'], $indicateur['id_indicateur'], $id_user, $annee)?? ($indicateur['type_valeur'] === 'Boolean' ? getBooleanFromOddTable(
              $pdo, 
              $id_odd, 
              $indicateur['code_indicateur'], 
              $id_user, 
              $annee
          )
          : null);
        $indicateur['score'] = $resultatFinal; 
      // echo $indicateur['code_indicateur'];
      // echo "\n";
    }
   

?>

<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tableau de Bord ODD Clair & Épuré</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body { background: #f8f9fa; }
    .card-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.3s; text-decoration: none; color: inherit; }
    .card-glass:hover { transform: translateY(-5px); }
    .icon-large { font-size: 2rem; opacity: 0.4; }
    .table-wrapper { max-height: 300px; overflow-y: auto; }
    canvas { width: 100% !important; height: auto !important; }
    .odd-logo { width: 24px; height: 24px; }
  </style>
</head>
<body>
  <div class="container-fluid p-5">
    <div class="row g-4 mb-4">
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-check-circle icon-large text-success"></i>
          <h5 class="mt-2">ODD Atteints</h5>
          <h2><?= htmlspecialchars($reached_num) ?></h2>
        </a>
      </div>
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-hourglass-split icon-large text-primary"></i>
          <h5 class="mt-2">ODD en cours</h5>
          <h2><?= htmlspecialchars($inprogress_num) ?></h2>
        </a>
      </div>
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-exclamation-circle icon-large text-warning"></i>
          <h5 class="mt-2">ODD en retard</h5>
          <h2>1</h2>
        </a>
      </div>
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-bell icon-large text-danger"></i>
          <h5 class="mt-2">Alertes</h5>
          <h2><?= $alert ?></h2>
        </a>
      </div>
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block">
          <i class="bi bi-plug icon-large text-info"></i>
          <h5 class="mt-2">API Connectées</h5>
          <h2>✓</h2>
          <small>Dernière synchro : 12:34</small>
        </a>
      </div>	  
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-patch-check icon-large text-info"></i>
          <h5 class="mt-2">Certification ISO</h5>
          <h2>✔</h2>
        </a>
      </div>
    </div>
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="card card-glass p-3 h-100">
          <h5>Liste des ODD</h5>
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>#</th><th>Objectif</th><th>État</th></tr></thead>
              <tbody>
                <!-- Exemple de liste complète -->
                <!-- Générer dynamiquement -->
                <!-- Pour démo, répéter -->
                <!-- Remplacer par boucle serveur -->
              <?php foreach($odds as $i => $odd): ?>
                <tr>
                  <td>
                    <img 
                    src="images/odd/odd<?= htmlspecialchars($i+1) ?>.svg"
                    class="odd-logo" 
                    alt="ODD <?= htmlspecialchars($i+1) ?>">
                </td>
                <td>
                  <?= htmlspecialchars_decode($odd["stitre"]) ?>
                </td>
                <td>
                  <span class="badge <?php echo $odd["status"] === 'Atteint' ?  "bg-success" : "bg-primary" ?>">
                    <?= htmlspecialchars($odd["status"]) ?>
                  </span>
                </td>
              </tr>
                
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6" id="graph-section">
        <div class="card card-glass p-3 h-100" >
          <h5>Graphique</h5>
          <canvas id="oddChart"></canvas>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <form action="indicateur.php" post="GET">
        <div class="card card-glass p-3">
         
            <h6>Accès par ODD</h6>
            <div class="card card-glass p-3">
              <h6>Accès par ODD</h6>
              <div class="d-flex gap-2">
                <select class="form-select odds-class" name='id_odd'>
                    <?php $selectedIndex = 1;?>
                    <?php foreach($odds as $i => $odd): ?>
                    <option value="<?= htmlspecialchars($i+1) ?>" data-value="<?= $i+1 ?>" >
                        ODD<?= htmlspecialchars($i+1) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select cibles-class">
                    <?php foreach($cibles as $i => $cible): ?>
                        <option value="<?= htmlspecialchars($i+1) ?>" data-value="<?= $cible['id_odd'] ?>" data-index=<?= htmlspecialchars($cible['code_cible']) ?>>
                            Cible <?= htmlspecialchars($cible['code_cible']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select indicators-class"  name="id_indicateur">
                    <?php foreach($indicateurs as $i => $indicateur): ?>
                       
                        <option value="<?= htmlspecialchars($i+1) ?>" data-value="<?= htmlspecialchars($indicateur['code_indicateur']) ?>" >
                            Indicateur <?= htmlspecialchars($indicateur['code_indicateur']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Aller</button>
            </div>
          </div>
           </form>
    </div>
    </div>
      
      <div class="col-md-6">
        <form action="indicateur.php" post="GET">
        <div class="card card-glass p-3">
          <h6>Accès par Titre</h6>
          <div class="d-flex gap-2">
            <select class="form-select indicators-class" name="id_indicateur">
                    <?php foreach($indicateurs as $i => $indicateur): ?>
                        <option value="<?= htmlspecialchars($i+1) ?>" data-value="<?= htmlspecialchars($indicateur['code_indicateur']) ?>" >
                            Indicateur <?= htmlspecialchars($indicateur['code_indicateur']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <button class="btn btn-outline-primary">Aller</button>
          </div>
        </div>
        </form>
      </div>
	  
    </div>

   <!-- Section finale avec 4 cards alignées, même hauteur, icônes, et "Paramètres" remplacé par "Recommandations" -->
<!-- Ligne finale avec 5 cards alignées sur toute la largeur -->
<div class="row g-4">
  <div class="col">
    <div class="card card-glass p-3 h-100 text-center">
      <i class="bi bi-file-earmark-text icon-large text-primary"></i>
      <h6>Rapports & Exports</h6>
      <div class="d-flex gap-2 justify-content-center">
        <button class="btn btn-outline-primary" onclick="generatePdf()">PDF</button>
        <button class="btn btn-outline-success" onclick="generateExcel()">Excel</button>
        <button class="btn btn-outline-secondary" onclick="createCSVFile()">CSV</button>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card card-glass p-3 h-100 text-center">
      <i class="bi bi-lightbulb icon-large text-warning"></i>
      <h6>Recommandations</h6>
      <button class="btn btn-outline-warning">Conseils pour améliorer</button>
    </div>
  </div>
  <div class="col">
    <div class="card card-glass p-3 h-100 text-center">
      <i class="bi bi-person icon-large text-dark"></i>
      <h6>Mon Profil</h6>
      <button class="btn btn-outline-dark">Modifier</button>
    </div>
  </div>
  <div class="col">
    <div class="card card-glass p-3 h-100 text-center">
      <i class="bi bi-gear icon-large text-info"></i>
      <h6>Logs & Paramètres API</h6>
      <div class="d-flex gap-2 justify-content-center">
        <button class="btn btn-outline-primary">Logs</button>
        <button class="btn btn-outline-secondary">Synchro</button>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card card-glass p-3 h-100 text-center">
      <i class="bi bi-geo-alt icon-large text-success"></i>
      <h6>Ma Ville</h6>
      <button class="btn btn-outline-success">Voir Détails</button>
    </div>
  </div>
</div>
 </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://raw.githack.com/eKoopmans/html2pdf/master/dist/html2pdf.bundle.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        const oddsEl = document.querySelector('.odds-class');
        const ciblesEl = document.querySelector('.cibles-class');
        const indicatorsEl = document.querySelector('.indicators-class');
        oddsEl.addEventListener('change', () => {
            const index = oddsEl.value;
            for(let i=0; i < ciblesEl.options.length ; i++ ){
                if (ciblesEl.options[i].getAttribute('data-value') === index) {
                    ciblesEl.value = i+1;
                    break;
                }
            }
            ciblesEl.dispatchEvent(new Event('change'));
        });


        ciblesEl.addEventListener('change', () => {
            const index = ciblesEl.value;
            const codeCible =  ciblesEl.options[index-1].getAttribute('data-index');
           
            for(let j=0; j < indicatorsEl.options.length ;  j++){
               
                let code = indicatorsEl.options[j].getAttribute('data-value');

                if(code === codeCible+".1"){

                    indicatorsEl.value = j + 1;
                    break;
                }
            }
            
        });
        
    </script>
  <script>

    const ctx = document.getElementById('oddChart').getContext('2d');
            const oddData = [
                <?php 
                    foreach ($odds as $odd): 
                        $background = 'rgba(40, 167, 69, 0.7)';
                        if($odd['score'] < $odd['threshhold']){
                          if(($odd['threshhold'] /  2)<= $odd['score']){
                            $background = 'rgba(242, 184, 25, 0.7)';
                          }
                          else if(($odd['threshhold'] / 2) > $odd['score']){
                            $background ='rgba(220, 53, 69, 0.7)';
                          }
                        }
                ?>
                {
                    label: "ODD <?= $odd['id_odd'] ?>",
                    code: "<?= $odd['id_odd'] ?>",
                    value: "<?= $odd['score'] ?>",
                    seuil: "<?= $odd['threshhold'] ?>",
                    color: "<?= $background ?>",
                  
                },
                <?php 
                        // endif;
                    endforeach; 
                ?>
        ];
            const chart = new Chart(ctx, {
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
                    label: 'Progression (%)',
                    data: oddData.map(item => item.value),
                    backgroundColor: oddData.map(item => item.color),
                    borderWidth: 1,
                    barPercentage: 0.7,
                    categoryPercentage: 0.7,
                    grouped: false
                }
            ]
                            },
            options: {
                scales: {
                y: { max: 100, beginAtZero: true, ticks: { stepSize: 20 } }
                }
            }
            });
    // function generatePdf(){
    //     // oddChart
    //     const histogram = document.getElementById('oddChart');
    //     const options = {
    //       margin:       9,
    //       filename:     'report.pdf',
    //       image:        { type: 'jpeg', quality: 1 },
    //       html2canvas:  { scale: 2 },
    //       jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    //   };
    //     html2pdf().set(options).from(histogram).save();
    // }


function generatePdf() {
    const container = document.createElement('div');
    container.style.padding = '20px';
    
    const table = document.createElement('table');
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.style.marginTop = '20px';
    

    const headers = ['Indicateur', 'Nom', 'Valeur', 'Seuil'];
    const headerRow = document.createElement('tr');
    headers.forEach(headerText => {
        const th = document.createElement('th');
        th.textContent = headerText;
        th.style.border = '1px solid #ddd';
        th.style.padding = '8px';
        th.style.backgroundColor = '#f2f2f2';
        headerRow.appendChild(th);
    });
    table.appendChild(headerRow);
    
    const rowData = <?= json_encode($indicateurs) ?>;
    
    rowData.forEach(indicateur => {
      
        const row = document.createElement('tr');

        const cell1 = document.createElement('td');
        cell1.textContent = indicateur.code_indicateur || '';
        cell1.style.border = '1px solid #ddd';
        cell1.style.padding = '8px';
        row.appendChild(cell1);
        
        const cell2 = document.createElement('td');
        cell2.textContent = indicateur.name || '';
        cell2.style.border = '1px solid #ddd';
        cell2.style.padding = '8px';
        cell2.style.whiteSpace = 'pre-wrap';
        cell2.style.wordWrap = 'break-word';
        cell2.style.minWidth = '100px';
        row.appendChild(cell2);
        
      
        const cell3 = document.createElement('td');
        cell3.textContent = indicateur.score || '';
        cell3.style.border = '1px solid #ddd';
        cell3.style.padding = '8px';
        row.appendChild(cell3);
        
        
        const cell4 = document.createElement('td');
        cell4.textContent = indicateur.threshold || '75'; 
        cell4.style.border = '1px solid #ddd';
        cell4.style.padding = '8px';
        row.appendChild(cell4);
        
        table.appendChild(row);
    });
    
    container.appendChild(table);
    
    
    const options = {
        margin: 10,
        filename: 'rapport_odd_' + new Date().toISOString().slice(0,10) + '.pdf',
        image: { type: 'jpeg', quality: 1 },
        html2canvas: { 
            scale: 2,
            logging: true 
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait'
        },
        pagebreak: {
            mode: ['css', 'legacy'],
            avoid: ['tr', 'td']
        },
         onclone: (clonedDoc) => {
            const style = document.createElement('style');
            style.innerHTML = `
                tr {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }
                [data-break-warning] {
                    background-color: rgba(255,0,0,0.1) !important;
                }
            `;
            clonedDoc.head.appendChild(style);
        }
    };

    html2pdf()
        .set(options)
        .from(container)
        .save()
        .then(() => {
            container.remove();
        });
}

function createCSVFile() {
  const indicatorsData = [
    <?php 
    foreach ($indicateurs as $indicateur): 
      $threshold = 75;
    ?>
    {
      Indicateur: <?= json_encode($indicateur['code_indicateur']) ?>,
      Valeur: <?= json_encode($indicateur['score'] ?? '') ?>,
      Seuil: <?= $threshold ?>,
      <?php if(isset($indicateur['name'])): ?>
      nom_indicateur: <?= json_encode($indicateur['name']) ?>
      <?php endif; ?>
    },
    <?php endforeach; ?>
  ];

  const headers = ["Indicateur", "Nom", "Valeur", "Seuil"];
  
  let csv = headers.join(',') + '\n';
  
  indicatorsData.forEach(item => {
    const row = [
      item.Indicateur,
      item.nom_indicateur || "",
      item.Valeur,
      item.Seuil
    ].map(value => {
      if (typeof value === 'string') {
        return `"${value.replace(/"/g, '""')}"`;
      }
      return value;
    });
    
    csv += row.join(',') + '\n';
  });
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'rapport_odd_' + new Date().toISOString().slice(0,10) + '.csv';
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
  

 function generateExcel() {

  const IndicatorsData = [
    <?php 
    foreach ($indicateurs as $indicateur): 
      $threshold = 75;
      $score =  $indicateur['score'] ?? 'NULL';
              
      error_log("Indicateur Code: " . $indicateur['code_indicateur']);
      error_log("Result: " . print_r($resultatFinal, true));
    ?>
    {
      Indicateur: "<?= $indicateur['code_indicateur'] ?>",
      Valeur: "<?= $score ?>",
      Seuil: <?= $threshold ?>,
      <?php if(isset($indicateur['name'])): ?>
      nom_indicateur: "<?= $indicateur['name'] ?>"
      <?php endif; ?>

    },
    <?php endforeach; ?>
  ];

  const currentDate = new Date().toISOString().slice(0, 10);
  
  const workbook = XLSX.utils.book_new();
  
  const wsData = [
    ["Indicateur", "Nom", "Valeur", "Seuil"]
  ];

  IndicatorsData.forEach(item => {
    wsData.push([
      item.Indicateur,
      item.nom_indicateur || "",
      item.Valeur,
      item.Seuil,
    ]);
  });

  const ws = XLSX.utils.aoa_to_sheet(wsData);
  
  ws['!cols'] = [
    { wch: 15 }, 
    { wch: 100 },  
    { wch: 15 },   
    { wch: 15 }   
  ];

  IndicatorsData.forEach((_, rowIndex) => {
    const cellAddress = XLSX.utils.encode_cell({ r: rowIndex + 1, c: 1 });
    if (!ws[cellAddress]) ws[cellAddress] = { t: 's', v: IndicatorsData[rowIndex].nom_indicateur || "" };
    ws[cellAddress].s = { alignment: { wrapText: true } };
  });


  XLSX.utils.book_append_sheet(workbook, ws, "Rapport ODD");

  XLSX.writeFile(workbook, `rapport_odd_${currentDate}.xlsx`);
}

  </script>
</body>
</html>

