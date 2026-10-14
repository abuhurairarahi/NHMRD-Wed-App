<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

function respond(int $statusCode, array $body): void
{
    http_response_code($statusCode);
    echo json_encode($body);
    exit;
}

function postText(string $key): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        respond(400, ['success' => false, 'error' => 'Invalid prescription data was submitted.']);
    }
    return trim($value);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['success' => false, 'error' => 'Only POST requests are allowed.']);
}

$patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
$doctorId = (int)($_SESSION['doctor_id'] ?? 1);
$title = postText('title');
$chiefComplaint = postText('chief_complaint');
$symptoms = postText('symptoms');
$currentCondition = postText('current_condition');
$doctorsStatement = postText('doctors_statement');
$nextVisitDate = postText('next_visit_date');
$visitType = postText('visit_type') ?: 'in_person';

if (!$patientId || $patientId < 1) {
    respond(400, ['success' => false, 'error' => 'A valid patient ID is required.']);
}
if ($title === '') {
    respond(400, ['success' => false, 'error' => 'A prescription title is required.']);
}
if (!in_array($visitType, ['in_person', 'telehealth'], true)) {
    respond(400, ['success' => false, 'error' => 'The visit type is invalid.']);
}
if ($nextVisitDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $nextVisitDate);
    if (!$date || $date->format('Y-m-d') !== $nextVisitDate) {
        respond(400, ['success' => false, 'error' => 'The next visit date is invalid.']);
    }
}

try {
    $patientCheck = $pdo->prepare('SELECT patient_id FROM patients WHERE patient_id = ?');
    $patientCheck->execute([$patientId]);
    if (!$patientCheck->fetch()) {
        respond(404, ['success' => false, 'error' => 'The requested patient could not be found.']);
    }

    $doctorCheck = $pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id = ?');
    $doctorCheck->execute([$doctorId]);
    if (!$doctorCheck->fetch()) {
        respond(403, ['success' => false, 'error' => 'A valid doctor account is required.']);
    }

    $medications = json_decode((string)($_POST['medications'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
    $labTests = json_decode((string)($_POST['lab_tests'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($medications) || array_values($medications) !== $medications || !is_array($labTests) || array_values($labTests) !== $labTests) {
        respond(400, ['success' => false, 'error' => 'Medication and test data must be lists.']);
    }

    foreach ($medications as $medication) {
        if (!is_array($medication) || !isset($medication['name']) || !is_string($medication['name']) || trim($medication['name']) === '') {
            respond(400, ['success' => false, 'error' => 'Each medication must have a name.']);
        }
        foreach (['strength', 'route', 'quantity', 'instructions'] as $field) {
            if (isset($medication[$field]) && !is_string($medication[$field])) {
                respond(400, ['success' => false, 'error' => 'Invalid medication details were submitted.']);
            }
        }
        $refills = filter_var($medication['refills'] ?? 0, FILTER_VALIDATE_INT);
        if ($refills === false || $refills < 0) {
            respond(400, ['success' => false, 'error' => 'Medication refills must be a non-negative integer.']);
        }
    }
    foreach ($labTests as $labTest) {
        if (!is_array($labTest) || !isset($labTest['name']) || !is_string($labTest['name']) || trim($labTest['name']) === '') {
            respond(400, ['success' => false, 'error' => 'Each diagnostic test must have a name.']);
        }
        foreach (['code', 'instructions'] as $field) {
            if (isset($labTest[$field]) && !is_string($labTest[$field])) {
                respond(400, ['success' => false, 'error' => 'Invalid diagnostic test details were submitted.']);
            }
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO prescriptions
            (patient_id, doctor_id, title, chief_complaint, symptoms, current_condition, doctors_statement, next_visit_date, visit_type, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\')'
    );
    $stmt->execute([
        $patientId,
        $doctorId,
        $title,
        $chiefComplaint,
        $symptoms,
        $currentCondition,
        $doctorsStatement,
        $nextVisitDate !== '' ? $nextVisitDate : null,
        $visitType
    ]);
    $prescriptionId = (int)$pdo->lastInsertId();

    if ($medications) {
        $medicationStmt = $pdo->prepare(
            'INSERT INTO prescription_medications
                (prescription_id, medication_name, dose_strength, route_frequency, dispense_quantity, refills, sig_instructions)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($medications as $medication) {
            $refills = filter_var($medication['refills'] ?? 0, FILTER_VALIDATE_INT);
            $medicationStmt->execute([
                $prescriptionId,
                trim($medication['name']),
                trim($medication['strength'] ?? ''),
                trim($medication['route'] ?? ''),
                trim($medication['quantity'] ?? ''),
                $refills,
                trim($medication['instructions'] ?? '')
            ]);
        }
    }

    if ($labTests) {
        $labTestStmt = $pdo->prepare(
            'INSERT INTO prescription_lab_tests (prescription_id, test_name, test_code, instructions)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($labTests as $labTest) {
            $labTestStmt->execute([
                $prescriptionId,
                trim($labTest['name']),
                trim($labTest['code'] ?? ''),
                trim($labTest['instructions'] ?? '')
            ]);
        }
    }

    $pdo->commit();
    respond(201, [
        'success' => true,
        'message' => 'Prescription saved successfully.',
        'prescription_id' => $prescriptionId
    ]);
} catch (JsonException $e) {
    respond(400, ['success' => false, 'error' => 'Medication or test data is not valid JSON.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Prescription save failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'error' => 'The prescription could not be saved. Please try again.']);
}
