# Live poem classification on Poetspedia

Mosab Qafya Detector was developed to support **poem-level Arabic qafya classification**. Its classification capability is already in use on the live [Poetspedia Arabic poetry site](https://poetspedia.com/), and visitors can inspect the live categorization on the [Arabic rhyme page](https://poetspedia.com/rhymes/).

This is a working implementation, not only a prototype proposal. The package supplies the programmatic layer for identifying rawi-based rhyme groups and returning structured poem-level results that applications can use for browsing, filtering, and editorial review.

## What this does—and does not—mean

- The package performs classification in a live application.
- It returns poem-level status, dominant/rawi grouping, clusters, per-ending details, detected defects, sanad findings, and diagnostics according to the implemented rules.
- It does **not** guarantee a correct scholarly interpretation for every Arabic poem. Unvocalized text, ambiguous word endings, rare forms, and mixed or irregular rhyme patterns can require review.
- The public [rhyme page](https://poetspedia.com/rhymes/) currently describes automated classification as experimental. Production use and ongoing quality improvement can coexist: the feature is operational, while difficult cases remain candidates for manual review.
- The seed reference corpus and regression fixtures are not a human-verified accuracy benchmark. Do not infer a general accuracy percentage from the fact that the feature is live or that automated tests pass.

## Integration guidance

For application-facing behavior, inspect the [architecture guide](architecture.md), [testing guide](testing.md), [rule registry](rule-registry.md). Treat `ok`, `review`, `partial`, and `error` as distinct states; preserve diagnostics and clusters in review tools instead of hiding them behind a single rhyme label.

## Live links

- [Poetspedia — Arabic poetry encyclopedia](https://poetspedia.com/)
- [Arabic rhyme classification by rawi](https://poetspedia.com/rhymes/)
