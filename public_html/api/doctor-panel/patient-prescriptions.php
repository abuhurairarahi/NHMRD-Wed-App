<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
if (!$patient_id || $patient_id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'A valid patient ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT patient_id, full_name, dob, gender, blood_group, NULL AS photo_url FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Patient not found.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT allergy_name, severity FROM patient_allergies WHERE patient_id = ? ORDER BY allergy_name");
    $stmt->execute([$patient_id]);
    $allergies = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT p.prescription_id, p.title, p.created_at, p.status,
                                  pm.medication_name, pm.dose_strength, pm.route_frequency,
                                  pm.dispense_quantity, pm.refills, pm.sig_instructions,
                                  d.full_name AS prescriber_name
                           FROM prescriptions p
                           LEFT JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
                           JOIN doctors d ON p.doctor_id = d.doctor_id
                           WHERE p.patient_id = ?
                           ORDER BY p.created_at DESC, p.prescription_id DESC, pm.id ASC
                           LIMIT 50");
    $stmt->execute([$patient_id]);
    $prescriptions = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'allergies' => $allergies,
        'prescriptions' => $prescriptions
    ]);
} catch (\PDOException $e) {
    error_log('Unable to load patient prescriptions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load patient prescriptions.']);
}
?>
