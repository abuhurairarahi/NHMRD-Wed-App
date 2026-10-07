<?php
ob_start();
require_once '../db.php';
ob_clean(); // clear any warnings or BOM that might have been printed
header('Content-Type: application/json');
try {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $patient_mrn = $_POST['patient_mrn'] ?? '';
        
        // 1. Find patient by MRN
        $stmt = $pdo->prepare("SELECT patient_id, full_name, phone FROM patients WHERE patient_id = ?");
        // Cast the second param to int if possible to avoid MySQL warnings
        $clean_id = str_replace('MRN-', '', $patient_mrn);
        $clean_id = is_numeric($clean_id) ? (int)$clean_id : 0;
        
        $stmt->execute([$clean_id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            echo json_encode(['success' => false, 'error' => 'Patient not found.']);
            exit;
        }

        $phone = $patient['phone'];
        if (!$phone) {
            echo json_encode(['success' => false, 'error' => 'Patient does not have a registered phone number.']);
            exit;
        }

        // 2. Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store in session (for demo) or DB

        $_SESSION['wa_otp_' . $patient['patient_id']] = $otp;

        // 3. Send WhatsApp OTP via WhatsApp Cloud API (Simulated)
        // For Demo: Return OTP in response so we can test the frontend easily
        echo json_encode([
            'success' => true, 
            'message' => "OTP sent to WhatsApp ($phone).", 
            'patient_id' => $patient['patient_id'],
            'demo_otp' => $otp // REMOVE IN PRODUCTION
        ]);

    } elseif ($action === 'verify_otp') {
        // 6-digit OTP Verification

        $patient_id = $_POST['patient_id'] ?? '';
        $otp = $_POST['otp'] ?? ''; 
        
        if (!$patient_id || !$otp) {
            echo json_encode(['success' => false, 'error' => 'Missing patient ID or OTP.']);
            exit;
        }

        // Verify OTP from session
        $session_otp_key = 'wa_otp_' . $patient_id;
        if (isset($_SESSION[$session_otp_key]) && (string)$_SESSION[$session_otp_key] === (string)$otp) {
            // OTP matches
            // unset($_SESSION[$session_otp_key]); // optional, but good for security
            
            // Log to database
            $stmtP = $pdo->prepare("SELECT full_name, dob FROM patients WHERE patient_id = ?");
            $stmtP->execute([$patient_id]);
            $pat = $stmtP->fetch();

            $insert = $pdo->prepare("
                INSERT INTO profile_access_requests (requesting_doctor_id, patient_full_name, patient_dob, patient_id, reason, status, decided_at)
                VALUES (?, ?, ?, ?, ?, 'approved', NOW())
            ");
            $doctor_id = $_SESSION['doctor_id'] ?? 1;
            $insert->execute([
                $doctor_id, 
                $pat['full_name'] ?? 'Unknown', 
                $pat['dob'] ?? null, 
                $patient_id, 
                'Requested via OTP verification process'
            ]);

            echo json_encode(['success' => true, 'message' => 'Identity Verified successfully. Profile unlocked.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP. Verification failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    }
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
