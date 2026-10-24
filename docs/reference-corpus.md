# Reference corpus and evaluation protocol

## Ground-truth policy

The initial `fixtures/reference-corpus/reference-corpus-v1.json` is deliberately a **seed**, not a verified scientific corpus. Its four records are imported from regression assertions. They are marked `review.status: unreviewed` and `split: holdout`; therefore they are excluded from accuracy and calibration metrics.

A record may be changed to `verified` only after a qualified reviewer checks the poem/source edition, the exact ending, the boundary, and each component. Required review metadata are `reviewer`, `reviewed_at`, and `rationale`. Expected component fields must all be present; use JSON `null` only when the reviewer has established that the component is absent. Do not copy detector output into `expected` and then call the row verified.

Record splits are immutable once used for reporting:

- `train`: development and rule-design examples.
- `validation`: threshold/profile fitting and calibration diagnostics.
- `test`: final held-out benchmark, used only for reporting.
- `holdout`: not evaluated yet or reserved for later review.

## Commands

```bash
php tools/validate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json
php benchmarks/evaluate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json test
```

To collect validation predictions for confidence diagnostics:

```bash
php benchmarks/evaluate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json validation /tmp/mosab-validation-predictions.json
php benchmarks/diagnose-confidence.php /tmp/mosab-validation-predictions.json --write-profile=/tmp/mosab-confidence-profile.json
```

Profile generation requires at least 100 verified validation records and at least three score bins with ten or more records each. It applies pool-adjacent-violators isotonic pooling to keep the score-to-accuracy mapping monotone, and creates a candidate profile marked `ready_for_holdout_test`, **not production-approved**. Test it on an independently labeled test split:

```bash
php benchmarks/evaluate-reference-corpus.php fixtures/reference-corpus/reference-corpus-v1.json test /tmp/mosab-test-predictions.json
php benchmarks/diagnose-confidence.php /tmp/mosab-test-predictions.json --profile=/tmp/mosab-confidence-profile.json --promote-profile=/tmp/mosab-confidence-validated.json

# Only after the independent holdout gate passes, load this separately validated profile at runtime.

```

The candidate profile does not affect runtime output. Evaluate it on independent test predictions; the `--promote-profile` option writes a separate `validated_on_holdout` profile only if the test set contains at least 100 verified records, mapped coverage is at least 80%, and the mapped Brier score improves over the raw score on those same mapped records. Only that validated profile is eligible for `confidence_calibration_profile`. Mosab preserves the raw `confidence.score` and exposes a separate `confidence.calibrated_score`. The profile is never fitted by the runtime.

## Metrics

The evaluator reports per-field exact accuracy for `rawi`, `rawi_haraka`, `radf`, `wasl`, `khurooj`, `taasis`, `dakhiil`, `segment_surface`, and `pattern_surface`, plus complete-record exact match. Only `review.status: verified` rows in the requested split contribute.

The raw detector score is a rule-based evidence score, not a probability. Raw-score ECE is therefore diagnostic, not a calibration certificate. Report test-set Brier score, mapped coverage, per-field accuracy, corpus size, and reviewer provenance together; do not report one scalar as proof of correctness.
