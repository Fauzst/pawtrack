<?php
// Temporary script to generate PDF for PetID P002 and save to storage for inspection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vet-class.php';

// Create Vet instance using $pdo from db.php
if (!isset($pdo) || !$pdo) {
    echo "Missing PDO instance in db.php\n";
    exit(1);
}

$vet = new Vet($pdo);
$petID = 'P002';

// We'll capture output by overriding FPDF Output to file. The method downloadFullPetRecord currently outputs directly using 'D'.
// To avoid modifying the class, we'll copy its logic here by calling the method but buffering output is not practical because the method sends headers.
// Instead, call the method but temporarily replace Output behavior by creating a new FPDF instance and invoking the same rendering logic isn't straightforward.

// Simpler approach: make an HTTP POST request to the download endpoint and save response. But network calls are disabled.
// So we'll simulate a minimal call by calling the public method but patching the Vet class isn't ideal in this temp script.

// Workaround: include the vet-class file and manually call the downloadFullPetRecord method but capture headers cannot be modified.
// We'll instead create a small wrapper that calls the internal PDF building portion by copying relevant parts - but to keep this script small, call the method and rely on Output('D') to send data to stdout which we'll redirect to a file when running php.

// Call method (it will send binary PDF to stdout). Run this script with: php tmp_generate_pdf.php > storage\\test_pet_P002.pdf

$vet->downloadFullPetRecord($petID);

// If we reach here, the method likely called exit after output. If not, print a message.
echo "Finished\n";
