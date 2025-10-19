<?php
header('Content-Type: application/json');
include 'fetch-users.php'; // adjust path
$fetch = new FetchUsers();
$fetch->getConnection();

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

    if (!isset($tables[$role]) || !isset($tables[$newRole])) {
        throw new Exception("Invalid role");
    }

    $source = $tables[$role];
    $target = $tables[$newRole];

    // 1. If role is unchanged, just update
    if ($role === $newRole) {
        $query = $fetch->conn->prepare("UPDATE {$source['table']} SET {$source['fname']}=?, {$source['lname']}=?, {$source['email']}=? WHERE {$source['idCol']}=?");
        $query->bind_param("ssss", $fname, $lname, $newEmail, $id);
        $query->execute();

        echo json_encode(["status"=>"success", "message"=>"User updated successfully"]);
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
    $query = $fetch->conn->prepare("INSERT INTO {$target['table']} ({$target['idCol']}, {$target['fname']}, {$target['lname']}, {$target['email']}, {$target['pass']}, {$target['pic']}, {$target['start']}) VALUES (?, ?, ?, ?, ?, ?, ?)");
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

    // Delete from source table
    $del = $fetch->conn->prepare("DELETE FROM {$source['table']} WHERE {$source['idCol']}=?");
    $del->bind_param("s", $id);
    $del->execute();

    echo json_encode(["status"=>"success", "message"=>"User role changed from {$role} to {$newRole} successfully"]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
