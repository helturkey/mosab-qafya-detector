# Release checklist for foundation patch 0.3.1

Run these from the repository root after applying the patch:

```bash
composer validate --strict
composer test
composer analyse
composer lint
php tools/validate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json
php benchmarks/evaluate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json test
```

The seed corpus has zero human-verified rows and is intentionally expected to report `no_verified_records_for_split`. That is not a passing accuracy benchmark; it means the scientific evaluation cannot yet be reported. Have a specialist verify and populate the corpus before asserting measured accuracy.

Before release:

1. Inspect `git diff` and the backup directory emitted by the installer.
2. Confirm the whole Pest suite, PHPStan, and Pint pass on the actual checkout.
3. Review changes to the serialized qafya segment: a `source_span` object is now exposed, and confidence has additive `calibrated_score`, `calibration_profile_id`, and `has_calibrated_estimate` fields.
4. Test API consumers that compare whole JSON structures, because fields were added additively.
5. Review all scholarly rule references at edition/page level; current registry status remains pending.
6. Do not call the confidence profile production-ready until it is evaluated on an independent test split with adequate sample size and coverage.
