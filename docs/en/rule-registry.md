# Rule registry, evidence, and trace auditing

`Mosab\QafyaDetector\Support\QafyaRuleRegistry` records the rule IDs emitted by word- and poem-level processing. Each entry describes its intent, layer, regression-test pointers, and available bibliographic leads.

## What the registry guarantees

- Rule IDs can be enumerated with `QafyaRuleRegistry::all()` and looked up with `QafyaRuleRegistry::get($id)`.
- Each entry links to implementation intent and test coverage.
- `unknownTraceRules()` identifies trace entries whose rule IDs are not registered.

The registry is an audit and maintenance tool; it does not certify scholarly validity. A regression test can show that behavior is stable without proving the underlying linguistic interpretation is correct. Registry entries currently carry a pending scholarly-review status and bibliographic references still need edition/page-level verification.

## Audit a saved trace

Save a word result as JSON and run:

```bash
php tools/audit-rule-trace.php /path/to/word-result.json
```

The command exits non-zero when the trace contains unregistered rule IDs. Add a registry entry and a regression test whenever introducing a new rule ID. Document preconditions, exceptions, and conflicts with other rules; add precise scholarly citation details before changing a review status.

## Evidence and alternative candidates

The word result places normalized evidence and uncertainty under `diagnostics.evidence` and `diagnostics.uncertainty`; candidate interpretations are available under `diagnostics.candidates`. The selected result is represented as a candidate. Some ambiguous hāʾ cases may include a structural alternative without a fabricated score. `candidate_set_complete: false` is intentional when the detector does not enumerate every possible rawi/wasl interpretation.

Consumers should present alternatives as evidence to review, not as equally validated predictions. Unknown rule IDs, unresolved candidate sets, warnings, and incomplete boundaries should remain visible in internal logs or review tooling.
