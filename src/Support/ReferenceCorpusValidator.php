<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/** Validates the auditable schema for human-reviewed qafya benchmark records. */
final class ReferenceCorpusValidator
{
    private const EXPECTED_FIELDS = [
        'rawi', 'rawi_haraka', 'radf', 'wasl', 'khurooj', 'taasis', 'dakhiil',
        'segment_surface', 'pattern_surface',
    ];

    /**
     * @param  array<string, mixed>  $corpus
     * @return list<string>
     */
    public function validate(array $corpus): array
    {
        $errors = [];
        if (($corpus['schema_version'] ?? null) !== 1) {
            $errors[] = 'schema_version must be integer 1.';
        }
        if (! is_array($corpus['records'] ?? null) || ! array_is_list($corpus['records'])) {
            return [...$errors, 'records must be a JSON list.'];
        }

        $seen = [];
        foreach ($corpus['records'] as $index => $record) {
            $prefix = 'records['.$index.']';
            if (! is_array($record)) {
                $errors[] = $prefix.' must be an object.';

                continue;
            }
            $id = $record['id'] ?? null;
            if (! is_string($id) || trim($id) === '') {
                $errors[] = $prefix.'.id must be a non-empty string.';
            } elseif (isset($seen[$id])) {
                $errors[] = $prefix.'.id duplicates '.$id.'.';
            } else {
                $seen[$id] = true;
            }

            if (! in_array($record['kind'] ?? null, ['ending', 'poem'], true)) {
                $errors[] = $prefix.'.kind must be ending or poem.';
            }
            if (! is_string($record['input'] ?? null) || trim($record['input']) === '') {
                $errors[] = $prefix.'.input must be a non-empty string.';
            }
            if (! in_array($record['split'] ?? null, ['train', 'validation', 'test', 'holdout'], true)) {
                $errors[] = $prefix.'.split must be train, validation, test, or holdout.';
            }

            $source = is_array($record['source'] ?? null) ? $record['source'] : [];
            foreach (['type', 'reference', 'locator'] as $key) {
                if (! is_string($source[$key] ?? null) || trim($source[$key]) === '') {
                    $errors[] = $prefix.'.source.'.$key.' is required for provenance.';
                }
            }

            $review = is_array($record['review'] ?? null) ? $record['review'] : [];
            $status = $review['status'] ?? null;
            if (! in_array($status, ['unreviewed', 'provisional', 'verified', 'rejected'], true)) {
                $errors[] = $prefix.'.review.status has an unsupported value.';

                continue;
            }
            if ($status !== 'verified') {
                continue;
            }

            foreach (['reviewer', 'reviewed_at', 'rationale'] as $key) {
                if (! is_string($review[$key] ?? null) || trim($review[$key]) === '') {
                    $errors[] = $prefix.'.review.'.$key.' is required for verified records.';
                }
            }
            if (is_string($review['reviewed_at'] ?? null) && strtotime($review['reviewed_at']) === false) {
                $errors[] = $prefix.'.review.reviewed_at must be a parseable date/time.';
            }

            $expected = is_array($record['expected'] ?? null) ? $record['expected'] : [];
            foreach (self::EXPECTED_FIELDS as $field) {
                if (! array_key_exists($field, $expected)) {
                    $errors[] = $prefix.'.expected.'.$field.' must be present (use null when reviewed and absent).';
                } elseif ($expected[$field] !== null && ! is_string($expected[$field])) {
                    $errors[] = $prefix.'.expected.'.$field.' must be string or null.';
                }
            }
            if (($record['kind'] ?? null) === 'poem' && ! is_array($expected['endings'] ?? null)) {
                $errors[] = $prefix.'.expected.endings is required for verified poem records.';
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $corpus */
    public function verifiedCount(array $corpus, ?string $split = null): int
    {
        $count = 0;
        foreach (($corpus['records'] ?? []) as $record) {
            if (! is_array($record) || ($record['review']['status'] ?? null) !== 'verified') {
                continue;
            }
            if ($split !== null && ($record['split'] ?? null) !== $split) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
