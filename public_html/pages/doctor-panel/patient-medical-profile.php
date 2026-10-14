<?php
session_start();
require_once __DIR__ . '/../../api/db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

// Fetch patient data
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile could not be loaded or access was not granted.");
}

// Calculate Age
$dob = new DateTime($patient['dob']);
$now = new DateTime();
$age = $now->diff($dob)->y;

// Fetch Medications
$stmtMed = $pdo->prepare("
    SELECT pm.*, p.created_at, d.full_name as doctor_name, d.designation
    FROM prescription_medications pm 
    JOIN prescriptions p ON pm.prescription_id = p.prescription_id 
    JOIN doctors d ON p.doctor_id = d.doctor_id 
    WHERE p.patient_id = ? 
    ORDER BY p.created_at DESC
");
$stmtMed->execute([$patient_id]);
$medications = $stmtMed->fetchAll();

// Fallback image if not present
$photo_url = $patient['photo_url'] ?? 'https://i.pravatar.cc/120?img=32';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Full Medical Profile</title>
  <link rel="stylesheet" href="../../assets/css/doctor-panel/patient-medical-profile.css">
  <link rel="stylesheet" href="../../assets/css/default-structure.css">
  <link rel="stylesheet" href="../../assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="../../assets/css/doctor-panel/features/doctor-subheader.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar doctor">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="../../pages/doctor-panel/dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="../../pages/doctor-panel/doctor-clinical-service-records.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="../../pages/doctor-panel/patient-appointments.php" class="nav-item active">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="../../pages/doctor-panel/doctor-profile.php" class="nav-item">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Doctor Profile</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" onclick="handleLogout()">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> 
          <span>Logout</span>
        </button>
      </div>
    </aside>


    <!-- Main Content Area -->
    <div class="main-wrapper">
      <!-- Top Header Bar -->
      <header class="doc-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search patient MRN, name, vitals...">
        </div>

        <div class="header-right">
          <div class="header-status-pill">
            <i class="fa-regular fa-clock"></i>
            <span>Shift: Morning Clinical Rounds (07:00 - 15:30)</span>
          </div>
          <div class="header-status-pill">
            <i class="fa-solid fa-rotate"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
          
          <div class="doctor-profile-badge">
            <div class="doctor-info-text">
              <span class="doctor-name">Dr. Nusrat Jahan, MD</span>
              <span class="doctor-dept">Internal Medicine & Therapeutics</span>
            </div>
            <div class="doctor-avatar">
              <img src="https://i.pravatar.cc/100?img=47" alt="Dr. Nusrat Jahan">
            </div>
          </div>
        </div>
      </header>

      <!-- Sub Header Navigation Bar -->
      <div class="subheader-bar">
        <div class="subheader-left">
          <span class="ehr-tag"><i class="fa-solid fa-folder-open"></i> Patient Electronic Health Record</span>
          <span class="status-pill-green">Active Clinical Encounter</span>
        </div>
        <div class="subheader-right">
          <div class="session-timer">
            <i class="fa-solid fa-user-clock"></i> Session: <span>01:58:40</span>
            <span class="tag-interactive">INTERACTIVE</span>
          </div>
          <a href="new-prescription-form.php?patient_id=<?= $patient_id ?>" class="btn-write-rx" style="text-decoration:none; display:inline-block;"><i class="fa-solid fa-pen-to-square"></i> Write Prescription</a>
        </div>
      </div>

      <!-- Module Navigation Tabs -->
      <nav class="module-tabs">
        <a href="../../pages/doctor-panel/patient-medical-profile.php?patient_id=<?= $patient_id ?>" class="tab-btn active"><i class="fa-solid fa-user"></i> Patient Profile</a>
        <a href="../../pages/doctor-panel/patient-prescription-records.php?patient_id=<?= $patient_id ?>" class="tab-btn"><i class="fa-solid fa-capsules"></i> Prescription Records</a>
        <a href="../../pages/doctor-panel/patient-surgary-records.php?patient_id=<?= $patient_id ?>" class="tab-btn"><i class="fa-solid fa-scalpel"></i> Surgery Records</a>
        <a href="../../pages/doctor-panel/patient-test-records.php?patient_id=<?= $patient_id ?>" class="tab-btn"><i class="fa-solid fa-vial"></i> Test Records</a>
        <a href="../../pages/doctor-panel/patient-vaccine-records.php?patient_id=<?= $patient_id ?>" class="tab-btn"><i class="fa-solid fa-syringe"></i> Vaccine Records</a>
      </nav>

      <!-- Main Content Body -->
      <main class="content-body">

        <!-- Patient Info Card -->
        <div class="patient-card">
          <div class="patient-main-info">
            <div class="patient-avatar-wrap">
              <img src="<?= htmlspecialchars($photo_url) ?>" alt="<?= htmlspecialchars($patient['full_name']) ?>">
              <span class="blood-badge"><?= htmlspecialchars($patient['blood_group'] ?? 'A+') ?></span>
            </div>

            <div class="patient-details">
              <div class="patient-name-row">
                <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span class="mrn-code">MRN-<?= $patient_id ?></span>
                <span class="otp-badge"><i class="fa-solid fa-lock-open"></i> Unlocked via OTP</span>
              </div>

              <div class="patient-meta-grid">
                <span><i class="fa-regular fa-calendar"></i> DOB: <?= htmlspecialchars(date('M d, Y', strtotime($patient['dob']))) ?> (<?= $age ?> y/o)</span>
                <span><i class="fa-solid fa-venus-mars"></i> <?= htmlspecialchars(ucfirst($patient['gender'])) ?></span>
                <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($patient['phone'] ?? '+880 1712-304829') ?></span>
                <span><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($patient['email'] ?? 'patient@nhmrd.gov.bd') ?></span>
              </div>

              <div class="patient-tags-row">
                <span class="attending-tag">Attending: <strong>Dr. Nusrat Jahan, MD</strong></span>
                <span class="allergy-alert"><i class="fa-solid fa-triangle-exclamation"></i> Allergy Alert: Sulfonamides (Mild Urticaria)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Vitals & Donor Widgets Grid -->
        <div class="vitals-grid">
          <!-- Blood Pressure Card -->
          <div class="vital-card">
            <div class="vital-header">
              <span class="vital-title">BLOOD PRESSURE</span>
              <span class="status-indicator-dot green"></span>
            </div>
            <div class="vital-body">
              <span class="vital-value"><?= htmlspecialchars($patient['blood_pressure'] ?? '118/76') ?> <small>mmHg</small></span>
            </div>
            <div class="vital-footer">
              <span class="tag-status-normal"><i class="fa-solid fa-circle-check"></i> Normal</span>
              <span class="vital-time">Today 08:30</span>
            </div>
          </div>

          <!-- Weight & Height Card -->
          <div class="vital-card">
            <div class="vital-header">
              <span class="vital-title">WEIGHT / HEIGHT</span>
              <i class="fa-solid fa-ruler-vertical vital-icon"></i>
            </div>
            <div class="vital-body">
              <span class="vital-value"><?= htmlspecialchars($patient['weight_kg'] ?? '64.2') ?> <small>kg</small></span>
              <span class="vital-subtext"><?= htmlspecialchars($patient['height_cm'] ?? '168') ?> cm</span>
            </div>
            <div class="vital-footer">
              <span class="vital-note">BMI: <?= htmlspecialchars($patient['bmi'] ?? '22.7') ?></span>
              <span class="vital-change">Stable</span>
            </div>
          </div>

          <!-- Organ Donor Card -->
          <div class="vital-card donor-card">
            <div class="vital-header">
              <span class="vital-title"><i class="fa-solid fa-heart-pulse green-text"></i> Organ & Blood Donor</span>
              <span class="active-verified-badge">ACTIVE VERIFIED</span>
            </div>
            <div class="donor-details">
              <strong>Tareq Hasan Chowdhury</strong>
              <span>Spouse / Proxy</span>
              <span>+880 1819-304981</span>
            </div>
            <div class="donor-footer">
              <span>Registry Key:</span>
              <strong>0003-PER-4882194</strong>
            </div>
          </div>
        </div>

        <!-- Lifetime Prescribed Medications Section -->
        <div class="medications-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <i class="fa-solid fa-capsules section-icon"></i>
              <div>
                <div class="title-with-badge">
                  <h3>Lifetime Prescribed Medications (Full Life Cycle History)</h3>
                  <span class="counter-badge">14 TOTAL LIFETIME COURSES</span>
                </div>
                <p class="section-subtitle">
                  Complete longitudinal pharmaceutical record from pediatric care to current adult regimen &bull; Verified via NHMRD HIE & National Rx Registry
                </p>
              </div>
            </div>

            <div class="section-actions">
              <div class="search-mini">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search drug name, ATC class, prescriber...">
              </div>
              <button class="btn-export"><i class="fa-solid fa-file-export"></i> Export Record</button>
            </div>
          </div>

          <!-- Timeline Group 1: Adult Current & Recent -->
          <div class="timeline-group">
            <div class="timeline-header">
              <span class="group-title"><i class="fa-solid fa-bookmark"></i> Adult Current & Recent <small>2020 - Present (Ages 32â€“36)</small></span>
              <a href="#" class="view-all">View All</a>
            </div>

            <div class="med-cards-list">
              <!-- Item 1 -->
              <div class="med-item green-border">
                <div class="med-icon green-bg"><i class="fa-solid fa-capsules"></i></div>
                <div class="med-info">
                  <div class="med-title">Ubrogepant (Ubrelvy) 50 mg</div>
                  <div class="med-details">Indication: Acute Migraine with Aura &bull; 1 tablet PO at onset, repeated once in 2h PRN (max 200mg/24h)</div>
                  <div class="med-meta">Prescribed: Oct 2023 - Present &bull; Prescriber: Dr. Nusrat Jahan, MD (Internal Med) &bull; Refills: 4 Remaining</div>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="med-item green-border">
                <div class="med-icon green-bg"><i class="fa-solid fa-pills"></i></div>
                <div class="med-info">
                  <div class="med-title">Propranolol HCl 40 mg ER</div>
                  <div class="med-details">Indication: Migraine Prophylaxis & Essential Tremor &bull; 1 cap PO QAM with food</div>
                  <div class="med-meta">Prescribed: Jan 2022 - Present &bull; Prescriber: Dr. Md. Mahmudul Hasan, MD (Neurology) &bull; Adherence: 98%</div>
                </div>
              </div>

              <!-- Item 3 -->
              <div class="med-item gray-border">
                <div class="med-icon gray-bg"><i class="fa-solid fa-ban"></i></div>
                <div class="med-info">
                  <div class="med-title text-muted">Sumatriptan (Imitrex) 50 mg</div>
                  <div class="med-details text-muted">Indication: Acute Migraine &bull; Substituted Oct 2023 due to transient chest tightness & poor tolerance; transitioned to Ubrogepant.</div>
                  <div class="med-meta">Prescribed: Mid 2020 - Sep 2023 (3.5 yrs) &bull; Prescriber: Dr. Md. Mahmudul Hasan, MD &bull; Outcome: Safely Discontinued</div>
                </div>
              </div>

              <!-- Item 4 -->
              <div class="med-item blue-border">
                <div class="med-icon blue-bg"><i class="fa-solid fa-check"></i></div>
                <div class="med-info">
                  <div class="med-title">Amoxicillin/Clavulanate (Augmentin) 875/125 mg</div>
                  <div class="med-details">Indication: Acute Bacterial Rhinosinusitis &bull; 1 tab PO BID x 10 days</div>
                  <div class="med-meta">Prescribed: Nov 12, 2021 - Nov 22, 2021 &bull; Prescriber: Dr. Nusrat Jahan, MD &bull; Outcome: Resolved without complications</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Timeline Group 2: Young Adult & University Years -->
          <div class="timeline-group">
            <div class="timeline-header">
              <span class="group-title"><i class="fa-solid fa-graduation-cap"></i> Young Adult & University Years <small>2007 - 2019 (Ages 19â€“31)</small></span>
              <a href="#" class="view-all">View All</a>
            </div>

            <div class="med-cards-list">
              <?php if (empty($medications)): ?>
                  <p>No medications found in the EHR registry.</p>
              <?php else: ?>
                  <?php foreach($medications as $med): ?>
                  <div class="med-item <?= strpos(strtolower($med['medication_name']), 'allergy') !== false ? 'red-border red-bg-card' : 'blue-border' ?>">
                    <div class="med-icon <?= strpos(strtolower($med['medication_name']), 'allergy') !== false ? 'red-bg' : 'blue-bg' ?>">
                        <i class="fa-solid <?= strpos(strtolower($med['medication_name']), 'allergy') !== false ? 'fa-triangle-exclamation' : 'fa-capsules' ?>"></i>
                    </div>
                    <div class="med-info">
                      <div class="med-title <?= strpos(strtolower($med['medication_name']), 'allergy') !== false ? 'red-text' : '' ?>">
                          <?= htmlspecialchars($med['medication_name'] . ' ' . $med['dose_strength']) ?>
                      </div>
                      <div class="med-details">
                          <?= htmlspecialchars($med['sig_instructions'] ?? $med['route_frequency']) ?>
                      </div>
                      <div class="med-meta">
                          Prescribed: <?= htmlspecialchars(date('M d, Y', strtotime($med['created_at']))) ?> 
                          &bull; Prescriber: <?= htmlspecialchars($med['doctor_name']) ?> (<?= htmlspecialchars($med['designation']) ?>) 
                          &bull; Refills: <?= htmlspecialchars($med['refills'] ?? '0') ?>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="../../assets/js/doctor-panel/patient-profile.js"></script>
</body>
</html>


