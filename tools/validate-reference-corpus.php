<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ReferenceCorpusValidator;

$root = dirname(__DIR__);
$path = $argv[1] ?? ($root.'/fixtures/reference-corpus/reference-corpus-v1.json');
if (! is_file($path)) {
    fwrite(STDERR, "Reference corpus file not found: {$path}\n");
    exit(2);
}
$json = file_get_contents($path);
if ($json === false) {
    fwrite(STDERR, "Cannot read reference corpus: {$path}\n");
    exit(2);
}
$data = json_decode($json, true);
if (! is_array($data)) {
    fwrite(STDERR, 'Invalid JSON: '.json_last_error_msg()."\n");
    exit(2);
}
require $root.'/vendor/autoload.php';
$validator = new ReferenceCorpusValidator;
$errors = $validator->validate($data);
if ($errors !== []) {
    fwrite(STDERR, "Reference corpus is invalid:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}
$records = is_array($data['records'] ?? null) ? $data['records'] : [];
$verified = $validator->verifiedCount($data);
$unreviewed = count($records) - $verified;
printf("Valid reference-corpus v1 file: %d records; %d verified; %d not verified.\n", count($records), $verified, $unreviewed);
if ($verified === 0) {
    fwrite(STDOUT, "No human-verified records are available for scoring. This is expected for the seed file.\n");
}
