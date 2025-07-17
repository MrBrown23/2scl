<?php 
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $_POST = array();   
    }
    
    
    $pdo = new PDO('mysql::host=localhost;dbname=odd_db;charset=utf8', 'root', '');
    $id_user = 1;
    $id_ville = 1;
    $areaCode = 250;
    $annee = date('Y');
    $_SESSION['id_user'] = $id_user;
    $_SESSION['id_ville'] = $id_ville;

    $_SESSION['annee'] = $annee;

    $id_idicateur = $_GET['id_indicateur'] ?? 1;


    $indicateur_array = [];
    $query = $pdo->prepare('SELECT * FROM indicateurs WHERE id_indicateur = ?');
    $query->execute([$id_idicateur]);
    $indicateur_array = $query->fetchAll(PDO::FETCH_ASSOC);
    $indicateur = $indicateur_array[0];
    
    $query = $pdo->prepare('SELECT valeur_quantitative FROM seuil_indicateur WHERE id_indicateur = ?');
    $query->execute([$id_idicateur]);
    $seuil_indicateur = $query->fetchAll(PDO::FETCH_ASSOC);
    require 'functions.php';
    $latestValue = getLatestValidValueFromApi($indicateur['code'], $areaCode);

    $indicateur['threshhold'] = $seuil_indicateur[0]['valeur_quantitative'];
    $id_odd = $_GET['id_odd'] ?? $indicateur['code'].str_split('.')[0] ;
    

    $resultatFinal = calculerIndicateur($pdo, $indicateur['formule'], $indicateur['id_indicateur'], $id_user, $annee)
      ?? ($indicateur['type_valeur'] === 'Boolean' ? getBooleanFromOddTable(
              $pdo, 
              $id_odd, 
              $indicateur['code'], 
              $id_user, 
              $annee
          )
          : null);
    
    $indicateur['score'] = $latestValue;
  
    

?>

<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Indicateur <?= $indicateur['code'] ?> Clair & Épuré</title>
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
          <h2>12</h2>
        </a>
      </div>
      <div class="col-md-2 flex-fill">
        <a href="#" class="card card-glass text-center p-3 d-block h-100">
          <i class="bi bi-hourglass-split icon-large text-primary"></i>
          <h5 class="mt-2">ODD en cours</h5>
          <h2>4</h2>
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
          <h2>2</h2>
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
    

     <div class="row-md-4">
      
        <div class="card card-glass p-3 h-100" >
          
          <div class="d-flex justify-content-between">
            <?php if($id_idicateur !== "1"): ?>
              <a href="/indicateur.php?id_indicateur=<?= htmlspecialchars($id_idicateur-1) ?>"
                  class="text-decoration-none">
                <button class="btn p-0 border-0" style="font-size: 2.5rem; color: #0d6efd;">
                  ⟵ 
                </button>
              </a>
            <?php else: ?>
              <a 
                  class="text-decoration-none">
                <button class="btn p-0 border-0" style="font-size: 2.5rem; color: #585b60ff;" disabled>
                  ⟵ 
                </button>
              </a>
            <?php endif; ?>


            <?php if($id_idicateur !== "247"): ?>
              <a href="/indicateur.php?id_indicateur=<?= htmlspecialchars($id_idicateur+1) ?>"
                  class="text-decoration-none">
                <button class="btn p-0 border-0" style="font-size: 2.5rem; color: #0d6efd;">
                  ⟶ 
                </button>
              </a>
            <?php else: ?>
              <a 
                  class="text-decoration-none">
                <button class="btn p-0 border-0" style="font-size: 2.5rem; color: #585b60ff;" disabled>
                  ⟶ 
                </button>
              </a>
            <?php endif; ?>           
          </div>
         
         
          <h5>Indicateur <?= htmlspecialchars($indicateur['code']) ?></h5>
          <p><?php echo $indicateur['nom_indicateur']; ?></p>
        </div>
      </div>
              
      <div class="row-md-4" id="graph-section">
        <div class="card card-glass p-3 h-100" >
          <h5>Graphique</h5>
          <canvas id="oddChart"></canvas>
        </div>
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
        <button class="btn btn-outline-secondary" onclick="generateExcel()">Excel</button>
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js" onerror="alert('Failed to load Chart.js!')"></script>

<!-- <script src="https://raw.githack.com/eKoopmans/html2pdf/master/dist/html2pdf.bundle.js"></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

  
  <script>
    const threshold = <?= $indicateur['threshhold']?? 0 ?>;
    const score = <?= $indicateur['score']?? 0 ?>;
    const ctx = document.getElementById('oddChart').getContext('2d');
    let background = 'rgba(40, 167, 69, 0.7)';
    if(score < threshold){
      if((threshold /  2)<= score){
        background = 'rgba(242, 184, 25, 0.7)';
      }
      else if((threshold / 2) > score){
        background ='rgba(220, 53, 69, 0.7)';
      }
    }
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ["Indicateur <?= htmlspecialchars($indicateur['code']) ?>"],
        datasets: [{
          label: 'Progression (%)',
          data: [`${score}`],
          backgroundColor: background, 
        },
        {
          label: 'Seuils (%)',
          data: [`${threshold}`],
          backgroundColor: 'rgba(100, 100, 100, 0.3)',        
        },
      ]
      },
       options: {
        responsive: true,
        scales: {
          y: {
            max: 100,
            beginAtZero: true,
            ticks: {
              stepSize: 20,
              callback: function(value) {
                return value + '%';
              }
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                const datasetLabel = context.dataset.label || '';
                const value = context.parsed.y;
                
                // For the threshold dataset, just show its value
                if (datasetLabel === 'Seuils (%)') {
                  return `${datasetLabel}: ${value}%`;
                }
                
                // For the score dataset, show comparison
                const diff = score - threshold;
                const prefix = diff >= 0 ? '+' : '';
                return [
                  `${datasetLabel}: ${value}%`,
                  `Seuil: ${threshold}%`,
                  `Écart: ${prefix}${diff.toFixed(1)}%`
                ];
              }
            }
          },
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 12,
              padding: 20
            }
          }
        }
      }
      
    });

 

function generateAnalysisText(score, threshold) {
  let mainText = '';
  let implications = '';
  
  if (threshold > score) {
    mainText = `Seuil (${threshold}) supérieur au score (${score})`;
    implications = `Observation: La valeur seuil dépasse le score obtenu\nImplication: Objectifs potentiellement non atteints\nRecommandation: Examiner les facteurs de performance`;
  } else if (score > threshold) {
    mainText = `Score (${score}) supérieur au seuil (${threshold})`;
    implications = `Observation: Performance excède les attentes\nImplication: Objectifs dépassés\nRecommandation: Maintenir les bonnes pratiques`;
  } else {
    mainText = `Score (${score}) égal au seuil (${threshold})`;
    implications = `Observation: Performance conforme aux attentes\nImplication: Objectifs atteints\nRecommandation: Évaluer les prochains objectifs`;
  }

  const name = "Indicateur <?php echo addslashes($indicateur['code']) ?> \n <?php echo addslashes($indicateur['nom_indicateur']); ?>"; 
  
  // Split long titles into multiple lines (max 40 characters per line)
  const splitTitle = (title) => {
    const words = title.split(' ');
    let lines = [''];
    let currentLine = 0;
    
    words.forEach(word => {
      if ((lines[currentLine] + word).length > 70) {
        currentLine++;
        lines[currentLine] = '';
      }
      lines[currentLine] += (lines[currentLine] ? ' ' : '') + word;
    });
    
    return lines.join('\n');
  };

  return {
    title: splitTitle(name),
    analysis: `\n\n${mainText}\n\n${implications}`
  };
}



// async function generatePdf() {
//   // 1. Convert chart to image
//   const canvas = document.getElementById('oddChart');
//   const chartImage = canvas.toDataURL('image/png');
  
//   // Get score and threshold (implement these functions)
//   const {title, analysis} = generateAnalysisText(score, threshold);
  
//   // 3. Create PDF
//   const pdf = new jspdf.jsPDF({
//     orientation: 'portrait',
//     unit: 'mm',
//     format: 'a4'
//   });

//   // 4. Add title with styling
//   pdf.setFont('helvetica', 'bold');
//   pdf.setTextColor(0, 0, 139); // Dark blue color
//   pdf.setFontSize(16);
//   pdf.text(title, 105, 15, {align: 'center'});

//   // 5. Add chart image (centered)
//   const imgWidth = 180;
//   const imgHeight = (canvas.height * imgWidth) / canvas.width;
//   const pageWidth = pdf.internal.pageSize.getWidth();
//   const xPos = (pageWidth - imgWidth) / 2;
  
//   pdf.addImage(chartImage, 'PNG', xPos, 25, imgWidth, imgHeight);
  
//   // 6. Add analysis text with styling
//   pdf.setFont('helvetica');
//   pdf.setFontSize(12);
//   pdf.setTextColor(0, 0, 0); // Black color
  
//   // Split text and add to PDF
//   const splitText = pdf.splitTextToSize(analysis, pageWidth - 40);
//   const textYPosition = 30 + imgHeight;
  
//   // Add colored section header
//   pdf.setFont('helvetica', 'bold');
//   pdf.setTextColor(70, 130, 180); // Steel blue
//   pdf.text('ANALYSE DE PERFORMANCE', 20, textYPosition);
  
//   // Add the rest of the text
//   pdf.setFont('helvetica', 'normal');
//   pdf.setTextColor(0, 0, 0);
//   pdf.text(splitText, 20, textYPosition + 10);
  
//   // 7. Save PDF
//   pdf.save('performance_report.pdf');
// }


async function generatePdf() {
  // 1. Convert chart to image
  const canvas = document.getElementById('oddChart');
  const chartImage = canvas.toDataURL('image/png');
  
  // Get score and threshold
  const {title, analysis} = generateAnalysisText(score, threshold);
  
  // 3. Create PDF
  const pdf = new jspdf.jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4'
  });

  // 4. Add title with styling (handles multi-line)
  pdf.setFont('helvetica', 'bold');
  pdf.setTextColor(0, 0, 139);
  pdf.setFontSize(16);
  
  // Split title into lines and add each line separately
  const titleLines = title.split('\n');
  let titleYPosition = 15;
  titleLines.forEach(line => {
    pdf.text(line.trim(), 20, titleYPosition, {align: 'left'});
    titleYPosition += 7; // Line height
  });

  // 5. Add chart image (centered)
  const imgWidth = 180;
  const imgHeight = (canvas.height * imgWidth) / canvas.width;
  const pageWidth = pdf.internal.pageSize.getWidth();
  const xPos = (pageWidth - imgWidth) / 2;
  
  pdf.addImage(chartImage, 'PNG', xPos, titleYPosition + 5, imgWidth, imgHeight);
  
  // 6. Add analysis text with proper line handling
  const textYPosition = titleYPosition + 10 + imgHeight;
  
  // Add section header
  pdf.setFont('helvetica', 'bold');
  pdf.setTextColor(70, 130, 180);
  pdf.text('ANALYSE DE PERFORMANCE', 20, textYPosition);
  
  // Process analysis text
  pdf.setFont('helvetica', 'normal');
  pdf.setTextColor(0, 0, 0);
  pdf.setFontSize(12);
  
  // Split into paragraphs and add each with proper spacing
  const paragraphs = analysis.split('\n\n');
  let currentY = textYPosition + 10;
  
  paragraphs.forEach(paragraph => {
    const lines = pdf.splitTextToSize(paragraph, pageWidth - 40);
    lines.forEach(line => {
      pdf.text(line.trim(), 20, currentY, {align: 'left'});
      currentY += 7; // Line height
    });
    currentY += 5; // Extra space between paragraphs
  });
  
  // 7. Save PDF
  pdf.save('performance_report.pdf');
}

function generateExcel() {
  // 1. Prepare data
  const {title, analysis} = generateAnalysisText(score, threshold);
  const currentDate = new Date().toISOString().slice(0, 10);
  
  // 2. Create workbook
  const workbook = XLSX.utils.book_new();
  
  // 3. Prepare worksheet data
  const wsData = [
    ["Rapport ODD", "", "", ""],
    ["Date:", currentDate, "", ""],
    ["", "", "", ""], // Empty row
    [title, "", "", ""],
    ["", "", "", ""], // Empty row
    ["Score", score, "", ""],
    ["Seuil", threshold, "", ""],
    ["Écart", score - threshold, "", ""],
    ["", "", "", ""], // Empty row
    ["ANALYSE DE PERFORMANCE", "", "", ""],
    ...analysis.split('\n').map(line => [line, "", "", ""]),
    ["", "", "", ""], // Empty row
    ["Détails des Indicateurs", "", "", ""],
    ["Code", "Nom", "Valeur", "Seuil", "Statut"]
  ];

  // Add ODD data rows
  oddData.forEach(item => {
    wsData.push([
      item.code,
      item.nom_indicateur || "",
      item.value,
      item.seuil,
      item.value >= item.seuil ? "Atteint" : "Non Atteint"
    ]);
  });

  // 4. Create worksheet
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  
  // 5. Apply styling through cell objects
  const wscols = [
    {wch: 30}, // Column A width
    {wch: 15}, // Column B width
    {wch: 15}, // Column C width
    {wch: 15}  // Column D width
  ];
  ws['!cols'] = wscols;

  // Merge title cells
  ws['!merges'] = [
    {s: {r: 0, c: 0}, e: {r: 0, c: 3}}, // Report title
    {s: {r: 3, c: 0}, e: {r: 3, c: 3}}, // Indicator title
    {s: {r: 9, c: 0}, e: {r: 9, c: 3}}, // Analysis header
    {s: {r: 12, c: 0}, e: {r: 12, c: 4}} // Indicators header
  ];

  // 6. Add worksheet to workbook
  XLSX.utils.book_append_sheet(workbook, ws, "Rapport ODD");

  // 7. Generate and download Excel file
  XLSX.writeFile(workbook, `rapport_odd_${currentDate}.xlsx`);
}


  </script>
</body>
</html>
