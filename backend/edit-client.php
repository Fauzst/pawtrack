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

    // split name
    $parts = explode(' ', $name, 2);
    $fname = $parts[0] ?? '';
    $lname = $parts[1] ?? '';

    // handle pic
    $picPath = null;
    if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../storage/images/C';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION);
        $file = 'C_' . time() . '.' . $ext;
        $target = $uploadDir . '/' . $file;
        if (move_uploaded_file($_FILES['pic']['tmp_name'], $target)) {
            $picPath = '/storage/images/C/' . $file;
        }
    }

    if ($picPath) {
        $sql = "UPDATE client SET ClientFName = ?, ClientLName = ?, ClientEmail = ?, ClientPic = ? WHERE ClientID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('sssss', $fname, $lname, $email, $picPath, $id);
    } else {
        $sql = "UPDATE client SET ClientFName = ?, ClientLName = ?, ClientEmail = ? WHERE ClientID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('ssss', $fname, $lname, $email, $id);
    }

    $ok = $stmt->execute();
    if (!$ok) throw new Exception($fetch->conn->error);

    // refresh session if same user
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['ClientID']) && $_SESSION['ClientID'] === $id) {
        $user = $fetch->getUserByID('client', $id);
        if ($user) {
            $_SESSION['ClientFName'] = $user['ClientFName'];
            $_SESSION['ClientLName'] = $user['ClientLName'];
            $_SESSION['ClientEmail'] = $user['ClientEmail'];
            $_SESSION['ClientPic'] = $user['ClientPic'];
        }
    }

    echo json_encode(['status'=>'success','message'=>'Client updated']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
