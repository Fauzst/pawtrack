<?php
header('Content-Type: application/json');
include 'fetch-users.php';
$fetch = new FetchUsers();
$fetch->getConnection();

try {
    if (empty($_POST['id'])) throw new Exception('Missing id');
    $id = $_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $vetSpec = trim($_POST['vetSpecialization'] ?? '');
    $vetLicense = trim($_POST['vetLicenseNo'] ?? '');
    $vetExp = trim($_POST['vetExperience'] ?? '');
    $vetContact = trim($_POST['vetContact'] ?? '');
    $clinic = trim($_POST['clinicBranch'] ?? '');

    $parts = explode(' ', $name, 2);
    $fname = $parts[0] ?? '';
    $lname = $parts[1] ?? '';

    $picPath = null;
    if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../storage/images/V';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION);
        $file = 'V_' . time() . '.' . $ext;
        $target = $uploadDir . '/' . $file;
        if (move_uploaded_file($_FILES['pic']['tmp_name'], $target)) {
            $picPath = '/storage/images/V/' . $file;
        }
    }

    if ($picPath) {
        $sql = "UPDATE vet SET VetFName = ?, VetSName = ?, VetEmail = ?, VetPic = ?, VetSpecialization = ?, VetLicenseNo = ?, VetExperience = ?, VetContact = ?, ClinicBranch = ? WHERE VetID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('ssssssisss', $fname, $lname, $email, $picPath, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinic, $id);
    } else {
        $sql = "UPDATE vet SET VetFName = ?, VetSName = ?, VetEmail = ?, VetSpecialization = ?, VetLicenseNo = ?, VetExperience = ?, VetContact = ?, ClinicBranch = ? WHERE VetID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('ssssssiss', $fname, $lname, $email, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinic, $id);
    }

    $ok = $stmt->execute();
    if (!$ok) throw new Exception($fetch->conn->error);

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['VetID']) && $_SESSION['VetID'] === $id) {
        $user = $fetch->getUserByID('vet', $id);
        if ($user) {
            $_SESSION['VetFName'] = $user['VetFName'];
            $_SESSION['VetSName'] = $user['VetSName'];
            $_SESSION['VetEmail'] = $user['VetEmail'];
            $_SESSION['VetPic'] = $user['VetPic'];
        }
    }

    echo json_encode(['status'=>'success','message'=>'Vet updated']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
