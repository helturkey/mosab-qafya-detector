# Rule registry and evidence

`Mosab\QafyaDetector\Support\QafyaRuleRegistry` inventories the rule IDs emitted by word- and poem-level analysis, including the purpose of each rule, its expected layer, and regression-test pointers. The registry describes implementation intent; it does **not** certify scholarly validity.

Every registry record currently uses `scholarly_review_status: pending`. Bibliographic pointers include *ميزان الذهب في صناعة شعر العرب* (باب القافية), but page-level citations, edition details, and direct review by a qafya specialist still need to be entered. Do not mark a rule verified solely because its regression tests pass.

Word results now normalize the existing `trace`, warnings, and ambiguities into `diagnostics.evidence` and `diagnostics.uncertainty`. The selected output is listed as a candidate. When the existing detector flags `haa_role_needs_poem_context`, an unranked structural alternative with final hāʾ as rawi is also emitted. It has no fabricated score. `candidate_set_complete: false` remains intentional: the detector does not yet enumerate or score every alternative rawi/wasl interpretation. Unknown trace rule IDs are made visible and flagged for review rather than silently treated as registered.

Audit a trace saved as JSON:

```bash
php tools/audit-rule-trace.php /path/to/word-result.json
```

The tool exits non-zero when trace rule IDs have not been registered. When a new rule is added, update the registry, link its regression tests, document conditions/exceptions, and add a scholarly source with edition and page before marking its review complete.
