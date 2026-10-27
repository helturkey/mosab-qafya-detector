# Release checklist

Use this checklist for each release. Run every command from the exact repository checkout being tagged.

## Automated gates

```bash
composer validate --strict
composer test
composer analyse
composer lint
```

## Review before tagging

- Inspect `git diff` and the complete list of changed files against the previous release.
- Verify compatibility changes to public method signatures, enums, serialized fields, defaults, and CLI exit codes.
- Run regression tests for every changed rule and inspect the affected JSON output, not only the assertion summary.
- Confirm all trace rule IDs are registered and the corresponding test fixtures exist.
- Check that README examples and both language documentation trees match the actual API.
- Review dependency and runtime-extension requirements on a clean install.
- Inspect `CHANGELOG.md` and ensure its behavior changes and compatibility notes match the release.
- Keep human-reviewed scholarly accuracy claims separate from software quality-gate results.
- Create the tag only after the required gates pass; publish a GitHub Release separately if a public release page is needed.

## After tagging

- Verify that the tag points to the intended commit and exists on the remote.
- Confirm the release notes, source archive, and installation instructions are visible.
- Test installation from the published tag in a clean temporary project.
