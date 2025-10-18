<?php
include($_SERVER['DOCUMENT_ROOT'] . "../backend/fetch-class.php");
session_start();
if (!isset($_SESSION['ClientID'])) {
    header("Location: /");
    exit();
}
    
$id = $_SESSION['ClientID'];
$fname = $_SESSION['ClientFName'];
$lname = $_SESSION['ClientLName'];
$email = $_SESSION['ClientEmail'];
$hashedPassword = $_SESSION['ClientPassword'];
$log = $_SESSION['ClientLog'];
$startDate = $_SESSION['ClientStartDate'];
$pic = $_SESSION['ClientPic'];

$fetch = new fetchClass();
$pets = $fetch->getClientPets($id);

?>