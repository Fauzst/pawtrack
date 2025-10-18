<!DOCTYPE html>
<html lang="en">



<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
include(__DIR__ . "/frontend/partials/head.php");
?>


<!-- DYNAMIC ROUTING -->
<?php 
//===== ROUTES ================
$routes = [
    "/" => "frontend/login.php",
    "/signup" => "frontend/signup.php",
    "/about" => "frontend/about.php",
    "/contact" => "frontend/contact.php",
    "/dashboard" => "frontend/dashboard.php",
    "/faqs" => "frontend/faqs.php",
    "/pets" => "frontend/pets.php"
];

//=============================
if (isset($routes[$path])) {
    include $routes[$path];
} else {
    http_response_code(404);
}
?>
  
</html>

