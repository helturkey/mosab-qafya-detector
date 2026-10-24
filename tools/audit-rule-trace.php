<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\QafyaRuleRegistry;

require dirname(__DIR__).'/vendor/autoload.php';
$path = $argv[1] ?? null;
$json = is_string($path) && is_file($path) ? file_get_contents($path) : stream_get_contents(STDIN);
$decoded = is_string($json) ? json_decode($json, true) : null;
if (! is_array($decoded)) {
    fwrite(STDERR, "Provide a JSON object or array containing a trace array, either as a file or via stdin.\n");
    exit(2);
}
$trace = $decoded['trace'] ?? $decoded;
if (! is_array($trace) || ! array_is_list($trace)) {
    fwrite(STDERR, "The input trace must be a list.\n");
    exit(2);
}
$unknown = QafyaRuleRegistry::unknownTraceRules($trace);
echo json_encode([
    'trace_rules' => count(array_filter($trace, static fn ($entry): bool => is_array($entry) && is_string($entry['rule'] ?? null))),
    'registered_rules' => count(QafyaRuleRegistry::all()),
    'unknown_rule_ids' => $unknown,
    'status' => $unknown === [] ? 'registered' : 'needs_registry_update',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
exit($unknown === [] ? 0 : 1);
