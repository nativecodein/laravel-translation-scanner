<?php

namespace NativeCode\TranslationScanner\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ScanTranslationsCommand extends Command
{
    protected $signature = 'translations:scan';

    protected $description = 'Scan Laravel and Inertia React files and append missing translation keys to resources/lang/en.json';

    public function handle(): void
    {
        $this->renderHeader();

        $translations = [];
        $addedKeys = [];
        $filesScanned = 0;

        $langPath = lang_path('en.json');

        /*
        |--------------------------------------------------------------------------
        | Load Existing Translations
        |--------------------------------------------------------------------------
        */

        if (File::exists($langPath)) {

            $translations = json_decode(
                File::get($langPath),
                true
            ) ?? [];
        }

        $initialCount = count($translations);

        $this->line('  <fg=magenta>></> Loaded <options=bold>' . $initialCount . '</> existing translation(s)');

        /*
        |--------------------------------------------------------------------------
        | Excluded Folders
        |--------------------------------------------------------------------------
        */

        $excludedFolders = [
            base_path('bootstrap'),
            base_path('config'),
            base_path('database'),
            base_path('routes'),
            base_path('storage'),
            base_path('tests'),
            base_path('vendor'),
            base_path('node_modules'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Allowed File Extensions
        |--------------------------------------------------------------------------
        */

        $extensions = [
            '.php',
            '.blade.php',
            '.js',
            '.ts',
            '.jsx',
            '.tsx',
        ];

        /*
        |--------------------------------------------------------------------------
        | Scan Project Files
        |--------------------------------------------------------------------------
        */

        $files = File::allFiles(base_path());
        $totalFiles = count($files);

        $this->line('  <fg=magenta>></> Scanning <options=bold>' . $totalFiles . '</> project file(s)');
        $this->newLine();

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->setFormat(
            '  <fg=cyan>%bar%</> <fg=white;options=bold>%percent:3s%%</> <fg=default>(%current%/%max%)</>'
        );
        $bar->setBarCharacter('=');
        $bar->setEmptyBarCharacter('-');
        $bar->setProgressCharacter('>');
        $bar->setBarWidth(40);
        $bar->start();

        foreach ($files as $file) {

            $bar->advance();

            $filePath = $file->getPathname();

            /*
            |--------------------------------------------------------------------------
            | Skip Excluded Folders
            |--------------------------------------------------------------------------
            */

            $skip = false;

            foreach ($excludedFolders as $excluded) {

                if (str_starts_with($filePath, $excluded)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Skip Laravel Root Files
            |--------------------------------------------------------------------------
            */

            if ($file->getPath() === base_path()) {

                $rootExcluded = [
                    'artisan',
                ];

                if (in_array($file->getFilename(), $rootExcluded)) {
                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Extension
            |--------------------------------------------------------------------------
            */

            $filename = $file->getFilename();

            $valid = false;

            foreach ($extensions as $extension) {

                if (str_ends_with($filename, $extension)) {
                    $valid = true;
                    break;
                }
            }

            if (! $valid) {
                continue;
            }

            $filesScanned++;

            $content = File::get($file);

            $results = [];

            /*
            |--------------------------------------------------------------------------
            | PHP / Blade Translation Scan
            |--------------------------------------------------------------------------
            |
            | Scan:
            | __('text')
            | trans('text')
            |
            */

            if (
                str_ends_with($filename, '.blade.php') ||
                str_ends_with($filename, '.php')
            ) {

                preg_match_all(
                    "/__\(['\"](.+?)['\"]\)/",
                    $content,
                    $matches1
                );

                preg_match_all(
                    "/trans\(['\"](.+?)['\"]\)/",
                    $content,
                    $matches2
                );

                $results = array_filter(
                    array_merge(
                        $matches1[1] ?? [],
                        $matches2[1] ?? []
                    )
                );
            } else {

                /*
                |--------------------------------------------------------------------------
                | JS / TS / JSX / TSX Variable Scan
                |--------------------------------------------------------------------------
                |
                | Detect:
                | const title = 'Dashboard'
                |
                */

                preg_match_all(
                    "/const\s+(\w+)\s*=\s*['\"](.+?)['\"]/",
                    $content,
                    $variableMatches,
                    PREG_SET_ORDER
                );

                $variables = [];

                foreach ($variableMatches as $match) {

                    $variables[$match[1]] = $match[2];
                }

                /*
                |--------------------------------------------------------------------------
                | JS / TS / JSX / TSX Translation Scan
                |--------------------------------------------------------------------------
                |
                | Scan:
                | t('text')
                | t("text")
                | t(`text`)
                | t(variable)
                |
                */

                preg_match_all(
                    "/t\(\s*([^)]+)\s*\)/",
                    $content,
                    $matches
                );

                foreach ($matches[1] as $value) {

                    $value = trim($value);

                    /*
                    |--------------------------------------------------------------------------
                    | Direct Strings
                    |--------------------------------------------------------------------------
                    */

                    if (
                        preg_match(
                            "/^['\"](.+?)['\"]$/",
                            $value,
                            $stringMatch
                        )
                    ) {

                        $results[] = $stringMatch[1];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Template Literals
                    |--------------------------------------------------------------------------
                    */ elseif (
                        preg_match(
                            "/^`(.+?)`$/",
                            $value,
                            $templateMatch
                        )
                    ) {

                        $results[] = $templateMatch[1];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Local Variables
                    |--------------------------------------------------------------------------
                    */ elseif (isset($variables[$value])) {

                        $results[] = $variables[$value];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Save Missing Translations
            |--------------------------------------------------------------------------
            */

            foreach ($results as $text) {

                $text = trim($text);

                if (empty($text)) {
                    continue;
                }

                // Skip translation keys
                if (str_contains($text, '.')) {
                    continue;
                }

                if (! isset($translations[$text])) {

                    $translations[$text] = $text;
                    $addedKeys[] = $text;
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        /*
        |--------------------------------------------------------------------------
        | Sort Translations
        |--------------------------------------------------------------------------
        */

        $this->line('  <fg=magenta>></> Sorting & saving <options=bold>en.json</>...');

        ksort($translations);

        /*
        |--------------------------------------------------------------------------
        | Save en.json
        |--------------------------------------------------------------------------
        */

        File::put(
            $langPath,
            json_encode(
                $translations,
                JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            )
        );

        $this->renderSummary(
            $initialCount,
            count($translations),
            $addedKeys,
            $filesScanned,
            $totalFiles
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Branded Header
    |--------------------------------------------------------------------------
    */

    private function renderHeader(): void
    {
        $line = str_repeat('=', 62);

        $this->newLine();
        $this->line('  <fg=magenta;options=bold>' . $line . '</>');
        $this->line('  <fg=cyan;options=bold>  Laravel Translation Scanner</>');
        $this->line('  <fg=default>  by </><fg=magenta;options=bold>NativeCode</><fg=default> . </><fg=blue>https://nativecode.in</>');
        $this->line('  <fg=magenta;options=bold>' . $line . '</>');
        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Summary Table & Footer
    |--------------------------------------------------------------------------
    */

    private function renderSummary(
        int $initialCount,
        int $finalCount,
        array $addedKeys,
        int $filesScanned,
        int $totalFiles
    ): void {

        $addedCount = count($addedKeys);

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>Scan Summary</>');
        $this->line('  <fg=magenta>' . str_repeat('-', 62) . '</>');

        $this->table(
            [
                '<fg=cyan;options=bold>Metric</>',
                '<fg=cyan;options=bold>Value</>',
            ],
            [
                ['Project files seen',  $totalFiles],
                ['Files scanned',       $filesScanned],
                ['Existing keys',       $initialCount],
                ['New keys added',      $addedCount],
                ['Total keys',          $finalCount],
            ]
        );

        if ($addedCount === 0) {

            $this->line('  <fg=green;options=bold>[OK]</> All translations are already up to date.');
        } else {

            $this->line('  <fg=green;options=bold>[OK]</> Added <options=bold>' . $addedCount . '</> new translation key(s) to <options=bold>en.json</>.');

            $preview = array_slice($addedKeys, 0, 10);

            $this->newLine();
            $this->line('  <fg=cyan>New keys (preview):</>');

            foreach ($preview as $key) {

                $this->line('    <fg=magenta>+</> ' . $key);
            }

            if ($addedCount > count($preview)) {

                $remaining = $addedCount - count($preview);

                $this->line('    <fg=default>... and ' . $remaining . ' more</>');
            }
        }

        $this->newLine();
        $this->line('  <fg=magenta>' . str_repeat('-', 62) . '</>');
        $this->line('  <fg=default>  Built with care by </><fg=magenta;options=bold>NativeCode</><fg=default> . </><fg=blue>https://nativecode.in</>');
        $this->line('  <fg=magenta>' . str_repeat('-', 62) . '</>');
        $this->newLine();
    }
}
