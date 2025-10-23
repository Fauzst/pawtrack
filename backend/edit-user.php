<?php
header('Content-Type: application/json');
include 'fetch-users.php'; // adjust path
$fetch = new FetchUsers();
$fetch->getConnection();

// Support both JSON body and multipart/form-data (for file uploads)
$isForm = !empty($_POST);
if ($isForm) {
    $id = $_POST['id'] ?? null;
    $role = $_POST['role'] ?? null;
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newRole = $_POST['newRole'] ?? $role;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['id'], $data['role'], $data['name'], $data['email'], $data['newRole'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit;
    }
    $id      = $data['id'];
    $role    = $data['role'];
    $newName = trim($data['name']);
    $newEmail = trim($data['email']);
    $newRole = $data['newRole'];
}

// --- DEBUG LOGGING (temporary) ---
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
$logFile = $logDir . '/edit-user-debug.log';
$debug = "\n---- " . date('c') . " ----\n";
$debug .= "isForm=" . ($isForm ? '1' : '0') . "\n";
$debug .= "_POST=" . print_r($_POST, true) . "\n";
$debug .= "_FILES=" . print_r($_FILES, true) . "\n";
file_put_contents($logFile, $debug, FILE_APPEND);

try {
    // Split name
    $nameParts = explode(" ", $newName, 2);
    $fname = $nameParts[0];
    $lname = isset($nameParts[1]) ? $nameParts[1] : "";

    // Table mapping
    $tables = [
        'admin' => ['table' => 'admin', 'idCol' => 'AdminID', 'fname' => 'AdminFName', 'lname' => 'AdminSName', 'email' => 'AdminEmail', 'pass' => 'AdminPassword', 'pic'=>'AdminPic', 'start'=>'AdminStartDate', 'prefix'=>'A'],
        'vet'   => ['table' => 'vet',   'idCol' => 'VetID',   'fname' => 'VetFName',   'lname' => 'VetSName',   'email' => 'VetEmail',   'pass'=>'VetPassword', 'pic'=>'VetPic', 'start'=>'VetStartDate', 'prefix'=>'V'],
        'client'=> ['table' => 'client','idCol' => 'ClientID','fname' => 'ClientFName','lname' => 'ClientLName','email' => 'ClientEmail','pass'=>'ClientPassword','pic'=>'ClientPic','start'=>'ClientStartDate','prefix'=>'C']
    ];

    // normalize and validate roles
    $role = is_string($role) ? strtolower(trim($role)) : '';
    $newRole = is_string($newRole) ? strtolower(trim($newRole)) : $role;

    if (!array_key_exists($role, $tables)) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Invalid or missing role: {$role}"]);
        exit;
    }
    if (!array_key_exists($newRole, $tables)) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Invalid newRole: {$newRole}"]);
        exit;
    }

    $source = $tables[$role];
    $target = $tables[$newRole];

    // 1. If role is unchanged, just update
    if ($role === $newRole) {
        // If a file was uploaded, handle saving before updating DB
        $newPicPath = null; // web path returned to client
        $storePicValue = null; // filename stored in DB
        if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
            // choose upload directory: admins go to 'admin' folder (old UI expects this), others use prefix folders
            if ($source['prefix'] === 'A') {
                $uploadDir = __DIR__ . '/../storage/images/admin';
            } else {
                $uploadDir = __DIR__ . '/../storage/images/' . $source['prefix'];
            }
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION);
            $filename = $source['prefix'] . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;
            $moved = false;
            if (move_uploaded_file($_FILES['pic']['tmp_name'], $targetPath)) {
                // Build web-accessible path for frontend preview
                if ($source['prefix'] === 'A') {
                    // store full web path for admins without the /pawtrack prefix
                    $newPicPath = '/storage/images/admin/' . $filename;
                    $storePicValue = $newPicPath; // store full path in DB for admin
                } else {
                    // store full web path for non-admins as well so frontends can use it directly
                    $newPicPath = '/storage/images/' . $source['prefix'] . '/' . $filename;
                    $storePicValue = $newPicPath;
                }
                $moved = true;
            }
            // log move result
            @file_put_contents($logFile, "moved=" . ($moved ? '1' : '0') . " target={$targetPath}\n", FILE_APPEND);
        }

        // collect vet-specific fields if present
        $vetSpec = $_POST['vetSpecialization'] ?? null;
        $vetLicense = $_POST['vetLicenseNo'] ?? null;
        $vetExp = $_POST['vetExperience'] ?? null;
        $vetContact = $_POST['vetContact'] ?? null;
        $clinicBranch = $_POST['clinicBranch'] ?? null;

        if (!empty($storePicValue)) {
            // store filename only in DB (actually storing web path string)
            if ($role === 'vet') {
                $sql = "UPDATE {$source['table']} SET {$source['fname']}=?, {$source['lname']}=?, {$source['email']}=?, {$source['pic']}=?, VetSpecialization=?, VetLicenseNo=?, VetExperience=?, VetContact=?, ClinicBranch=? WHERE {$source['idCol']}=?";
                $values = [$fname, $lname, $newEmail, $storePicValue, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinicBranch, $id];
                @file_put_contents($logFile, "prepared_sql=" . $sql . "\nvalues=" . print_r($values, true) . "\n", FILE_APPEND);
                $query = $fetch->conn->prepare($sql);
                $query->bind_param("ssssssisss", $fname, $lname, $newEmail, $storePicValue, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinicBranch, $id);
            } else {
                $sql = "UPDATE {$source['table']} SET {$source['fname']}=?, {$source['lname']}=?, {$source['email']}=?, {$source['pic']}=? WHERE {$source['idCol']}=?";
                $values = [$fname, $lname, $newEmail, $storePicValue, $id];
                @file_put_contents($logFile, "prepared_sql=" . $sql . "\nvalues=" . print_r($values, true) . "\n", FILE_APPEND);
                $query = $fetch->conn->prepare($sql);
                $query->bind_param("sssss", $fname, $lname, $newEmail, $storePicValue, $id);
            }
        } else {
            if ($role === 'vet') {
                $sql = "UPDATE {$source['table']} SET {$source['fname']}=?, {$source['lname']}=?, {$source['email']}=?, VetSpecialization=?, VetLicenseNo=?, VetExperience=?, VetContact=?, ClinicBranch=? WHERE {$source['idCol']}=?";
                $values = [$fname, $lname, $newEmail, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinicBranch, $id];
                @file_put_contents($logFile, "prepared_sql=" . $sql . "\nvalues=" . print_r($values, true) . "\n", FILE_APPEND);
                $query = $fetch->conn->prepare($sql);
                $query->bind_param("ssssssiss", $fname, $lname, $newEmail, $vetSpec, $vetLicense, $vetExp, $vetContact, $clinicBranch, $id);
            } else {
                $sql = "UPDATE {$source['table']} SET {$source['fname']}=?, {$source['lname']}=?, {$source['email']}=? WHERE {$source['idCol']}=?";
                $values = [$fname, $lname, $newEmail, $id];
                @file_put_contents($logFile, "prepared_sql=" . $sql . "\nvalues=" . print_r($values, true) . "\n", FILE_APPEND);
                $query = $fetch->conn->prepare($sql);
                $query->bind_param("ssss", $fname, $lname, $newEmail, $id);
            }
        }
        $execOk = $query->execute();
        $err = $fetch->conn->error;
        $affected = $fetch->conn->affected_rows;
        @file_put_contents($logFile, "executeOk=" . ($execOk ? '1' : '0') . " error=" . $err . " affected=" . $affected . "\n", FILE_APPEND);

        // Fetch updated user row and update session if this is the logged-in user
        $updatedUser = $fetch->getUserByID($role, $id);
        if (!$updatedUser) {
            @file_put_contents($logFile, "warning: getUserByID returned null for role={$role} id={$id}\n", FILE_APPEND);
        }
        // If the current session user matches, update session vars
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $sessId = $_SESSION['ClientID'] ?? $_SESSION['VetID'] ?? $_SESSION['AdminID'] ?? null;
        if ($sessId && $sessId === $id && is_array($updatedUser)) {
            // Write back common session keys depending on role
            if ($role === 'client') {
                $_SESSION['ClientFName'] = $updatedUser[$source['fname']] ?? $_SESSION['ClientFName'];
                $_SESSION['ClientLName'] = $updatedUser[$source['lname']] ?? $_SESSION['ClientLName'];
                $_SESSION['ClientEmail'] = $updatedUser[$source['email']] ?? $_SESSION['ClientEmail'];
                if (!empty($updatedUser[$source['pic']])) $_SESSION['ClientPic'] = $updatedUser[$source['pic']];
            } elseif ($role === 'vet') {
                $_SESSION['VetFName'] = $updatedUser[$source['fname']] ?? $_SESSION['VetFName'];
                $_SESSION['VetSName'] = $updatedUser[$source['lname']] ?? $_SESSION['VetSName'];
                $_SESSION['VetEmail'] = $updatedUser[$source['email']] ?? $_SESSION['VetEmail'];
                if (!empty($updatedUser[$source['pic']])) $_SESSION['VetPic'] = $updatedUser[$source['pic']];
            } elseif ($role === 'admin') {
                $_SESSION['AdminFName'] = $updatedUser[$source['fname']] ?? $_SESSION['AdminFName'];
                $_SESSION['AdminSName'] = $updatedUser[$source['lname']] ?? $_SESSION['AdminSName'];
                $_SESSION['AdminEmail'] = $updatedUser[$source['email']] ?? $_SESSION['AdminEmail'];
                if (!empty($updatedUser[$source['pic']])) $_SESSION['AdminPic'] = $updatedUser[$source['pic']];
            }
        }

        // Return updated pic path as data if available and include updated user row
        $response = ["status"=>($execOk ? "success" : "error"), "message"=>($execOk?"User updated successfully":"Update failed: " . $err)];
        if ($newPicPath) $response['data']['pic'] = $newPicPath;
        $response['data']['user'] = $updatedUser;
        echo json_encode($response);
        exit;
    }

    // 2. Role changed: migrate data
    // Fetch source user data
    $userData = $fetch->getUserByID($role, $id);
    if (!$userData) throw new Exception("User not found");

    // Generate new ID for target table
    $res = $fetch->conn->query("SELECT {$target['idCol']} FROM {$target['table']} ORDER BY {$target['idCol']} DESC LIMIT 1");
    $lastId = $res->fetch_assoc()[$target['idCol']] ?? null;
    if ($lastId) {
        $num = (int)substr($lastId, 1) + 1;
    } else {
        $num = 1;
    }
    $newId = $target['prefix'] . str_pad($num, 3, '0', STR_PAD_LEFT);

    // Insert into target table
    $insSql = "INSERT INTO {$target['table']} ({$target['idCol']}, {$target['fname']}, {$target['lname']}, {$target['email']}, {$target['pass']}, {$target['pic']}, {$target['start']}) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insValues = [ $newId, $userData[$source['fname']], $userData[$source['lname']], $userData[$source['email']], '***', $userData[$source['pic']], $userData[$source['start']] ];
    @file_put_contents($logFile, "prepared_sql=" . $insSql . "\nvalues=" . print_r($insValues, true) . "\n", FILE_APPEND);
    $query = $fetch->conn->prepare($insSql);
    $query->bind_param(
        "sssssss",
        $newId,
        $userData[$source['fname']],
        $userData[$source['lname']],
        $userData[$source['email']],
        $userData[$source['pass']],
        $userData[$source['pic']],
        $userData[$source['start']]
    );
    $query->execute();
    @file_put_contents($logFile, "insert_execute_ok=" . ($query ? '1' : '0') . " error=" . $fetch->conn->error . "\n", FILE_APPEND);

    // Delete from source table
    $delSql = "DELETE FROM {$source['table']} WHERE {$source['idCol']}=?";
    @file_put_contents($logFile, "prepared_sql=" . $delSql . "\nvalues=" . print_r([$id], true) . "\n", FILE_APPEND);
    $del = $fetch->conn->prepare($delSql);
    $del->bind_param("s", $id);
    $del->execute();

    echo json_encode(["status"=>"success", "message"=>"User role changed from {$role} to {$newRole} successfully"]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
