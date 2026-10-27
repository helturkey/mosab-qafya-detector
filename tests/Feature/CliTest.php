<?php

declare(strict_types=1);

/**
 * Run bin/qafya in a child process.
 *
 * @param  list<string>  $args
 * @return array{code: int, out: string, err: string}
 */
function run_qafya_cli(array $args): array
{
    $command = array_merge([PHP_BINARY, dirname(__DIR__, 2).'/bin/qafya'], $args);
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

    expect($process)->not->toBeFalse();

    /** @var array<int, resource> $pipes */
    $out = (string) stream_get_contents($pipes[1]);
    $err = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['code' => proc_close($process), 'out' => $out, 'err' => $err];
}

function write_cli_poem(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'qafya-poem-');

    expect($path)->toBeString();

    file_put_contents((string) $path, $contents);

    return (string) $path;
}

it('prints usage for help or no arguments', function (): void {
    foreach ([[], ['--help'], ['-h'], ['help']] as $args) {
        $run = run_qafya_cli($args);

        expect($run['code'])->toBe(0)
            ->and($run['out'])->toContain('الاستخدام')
            ->and($run['err'])->toBe('');
    }
});

it('prints the components of a word in text form', function (): void {
    $run = run_qafya_cli(['word', 'كتابُهُ']);

    expect($run['code'])->toBe(0)
        ->and($run['out'])->toContain('الحالة: ok')
        ->and($run['out'])->toContain('الروي: ب')
        ->and($run['out'])->toContain('الردف: ا')
        ->and($run['out'])->toContain('الوصل: ه')
        ->and($run['out'])->toContain('الخروج: —');
});

it('prints a separator between several words', function (): void {
    $run = run_qafya_cli(['word', 'كتابْ', 'حسابْ']);

    expect($run['code'])->toBe(0)
        ->and($run['out'])->toContain(str_repeat('-', 40))
        ->and(substr_count($run['out'], 'الروي: ب'))->toBe(2);
});

it('prints JSON for words', function (): void {
    $run = run_qafya_cli(['word', 'كتابُهُ', 'ناداها', '--json']);
    $data = json_decode($run['out'], true, flags: JSON_THROW_ON_ERROR);

    expect($run['code'])->toBe(0)
        ->and($data)->toBeArray()->toHaveCount(2)
        ->and($data[0]['status'])->toBe('ok')
        ->and($data[0])->toHaveKeys(['input', 'segment', 'qafya', 'signatures', 'pattern', 'confidence', 'diagnostics', 'trace'])
        ->and($data[1]['status'])->toBe('ok');
});

it('rejects a word command without a word', function (): void {
    $run = run_qafya_cli(['word']);

    expect($run['code'])->toBe(2)
        ->and($run['err'])->toContain('أدخل كلمة واحدة على الأقل');
});

it('rejects an unknown command, option, or mode', function (): void {
    $command = run_qafya_cli(['frobnicate']);
    $option = run_qafya_cli(['word', 'كتابْ', '--bogus']);
    $mode = run_qafya_cli(['word', 'كتابْ', '--mode=bad']);

    expect($command['code'])->toBe(2)
        ->and($command['err'])->toContain('أمر غير معروف: frobnicate')
        ->and($option['code'])->toBe(2)
        ->and($option['err'])->toContain('خيار غير معروف: --bogus')
        ->and($mode['code'])->toBe(2)
        ->and($mode['err'])->toContain('--mode');
});

it('accepts every documented mode', function (): void {
    foreach (['auto', 'heuristic', 'scholarly'] as $mode) {
        $run = run_qafya_cli(['word', 'كتابْ', "--mode={$mode}", '--no-strict-harakat']);

        expect($run['code'])->toBe(0, $mode);
    }
});

it('analyses a poem file in text form', function (): void {
    $path = write_cli_poem("صدر أول\nهذا كتابُهُ\nصدر ثان\nهذا حسابُهُ\n");
    $run = run_qafya_cli(['poem', $path]);
    unlink($path);

    expect($run['code'])->toBe(0)
        ->and($run['out'])->toContain('الحالة: ok')
        ->and($run['out'])->toContain('عدد الأبيات: 2')
        ->and($run['out'])->toContain('عدد النهايات المحللة: 2')
        ->and($run['out'])->toContain('الروي الغالب: ب')
        ->and($run['out'])->toContain('نسبة القافية الغالبة: 1.0000')
        ->and($run['out'])->toContain('قوافٍ متعددة: لا')
        ->and($run['out'])->toContain('عدد العيوب: 0');
});

it('analyses a poem file as JSON and honors the analysis flags', function (): void {
    $path = write_cli_poem("كلامْ***سلامْ\nظلامْ***نُومْ\n");
    $sadr = json_decode(run_qafya_cli(['poem', $path, '--json', '--analyze-sadr'])['out'], true, flags: JSON_THROW_ON_ERROR);
    $plain = json_decode(run_qafya_cli(['poem', $path, '--json'])['out'], true, flags: JSON_THROW_ON_ERROR);
    $noSanad = json_decode(run_qafya_cli(['poem', $path, '--json', '--no-sanad', '--analyze-sadr'])['out'], true, flags: JSON_THROW_ON_ERROR);
    $noDefects = json_decode(run_qafya_cli(['poem', $path, '--json', '--no-defects', '--analyze-sadr'])['out'], true, flags: JSON_THROW_ON_ERROR);
    unlink($path);

    expect($sadr['endings'])->toHaveCount(4)
        ->and($plain['endings'])->toHaveCount(2)
        ->and($sadr['defects'])->not->toBeEmpty()
        ->and($noSanad['sanad'])->toBe([])
        ->and($noSanad['defects'])->not->toBeEmpty()
        ->and($noDefects['defects'])->toBe([])
        ->and($noDefects['sanad'])->toBe([]);
});

it('rejects a missing poem file or the wrong number of arguments', function (): void {
    $missing = run_qafya_cli(['poem', '/nonexistent/qafya-poem.txt']);
    $none = run_qafya_cli(['poem']);
    $many = run_qafya_cli(['poem', 'a.txt', 'b.txt']);

    expect($missing['code'])->toBe(2)
        ->and($missing['err'])->toContain('ملف القصيدة غير موجود')
        ->and($none['code'])->toBe(2)
        ->and($many['code'])->toBe(2);
});
