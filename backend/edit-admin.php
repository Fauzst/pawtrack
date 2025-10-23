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

    $parts = explode(' ', $name, 2);
    $fname = $parts[0] ?? '';
    $lname = $parts[1] ?? '';

    $picPath = null;
    if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../storage/images/admin';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION);
        $file = 'A_' . time() . '.' . $ext;
        $target = $uploadDir . '/' . $file;
        if (move_uploaded_file($_FILES['pic']['tmp_name'], $target)) {
            $picPath = '/storage/images/admin/' . $file;
        }
    }

    if ($picPath) {
        $sql = "UPDATE admin SET AdminFName = ?, AdminSName = ?, AdminEmail = ?, AdminPic = ? WHERE AdminID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('sssss', $fname, $lname, $email, $picPath, $id);
    } else {
        $sql = "UPDATE admin SET AdminFName = ?, AdminSName = ?, AdminEmail = ? WHERE AdminID = ?";
        $stmt = $fetch->conn->prepare($sql);
        $stmt->bind_param('ssss', $fname, $lname, $email, $id);
    }

    $ok = $stmt->execute();
    if (!$ok) throw new Exception($fetch->conn->error);

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['AdminID']) && $_SESSION['AdminID'] === $id) {
        $user = $fetch->getUserByID('admin', $id);
        if ($user) {
            $_SESSION['AdminFName'] = $user['AdminFName'];
            $_SESSION['AdminSName'] = $user['AdminSName'];
            $_SESSION['AdminEmail'] = $user['AdminEmail'];
            $_SESSION['AdminPic'] = $user['AdminPic'];
        }
    }

    echo json_encode(['status'=>'success','message'=>'Admin updated']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
