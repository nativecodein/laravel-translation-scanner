<?php

namespace NativeCodeIn\TranslationScanner\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TranslateJsonCommand extends Command
{
    protected $signature = 'translations:translate {locales*}';

    protected $description = 'Translate en.json to multiple languages';

    public function handle(): void
    {
        $this->renderHeader();

        $locales = $this->argument('locales');

        $sourcePath = lang_path('en.json');

        /*
        |--------------------------------------------------------------------------
        | Check Source File
        |--------------------------------------------------------------------------
        */

        if (! File::exists($sourcePath)) {

            $this->error('en.json not found.');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Load English Translations
        |--------------------------------------------------------------------------
        */

        $translations = json_decode(
            File::get($sourcePath),
            true
        ) ?? [];

        $totalKeys = count($translations);

        $this->line(
            '  <fg=magenta>></> Loaded <options=bold>' .
                $totalKeys .
                '</> translation key(s)'
        );

        $this->line(
            '  <fg=magenta>></> Translating <options=bold>' .
                count($locales) .
                '</> locale(s)'
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Progress Bar
        |--------------------------------------------------------------------------
        */

        $bar = $this->output->createProgressBar(
            $totalKeys * count($locales)
        );

        $bar->setFormat(
            '  <fg=cyan>%bar%</> <fg=white;options=bold>%percent:3s%%</> <fg=default>(%current%/%max%)</>'
        );

        $bar->setBarCharacter('=');
        $bar->setEmptyBarCharacter('-');
        $bar->setProgressCharacter('>');
        $bar->setBarWidth(40);

        $bar->start();

        /*
        |--------------------------------------------------------------------------
        | Translate Multiple Locales
        |--------------------------------------------------------------------------
        */

        foreach ($locales as $locale) {

            $locale = trim($locale, ', ');

            if (empty($locale)) {
                continue;
            }

            $targetPath = lang_path("{$locale}.json");

            $translated = [];

            foreach ($translations as $key => $value) {

                $translated[$key] = $this->translate(
                    $value,
                    $locale
                );

                $bar->advance();
            }

            /*
            |--------------------------------------------------------------------------
            | Save Locale JSON
            |--------------------------------------------------------------------------
            */

            File::put(
                $targetPath,
                json_encode(
                    $translated,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                )
            );
        }

        $bar->finish();

        $this->newLine(2);

        /*
        |--------------------------------------------------------------------------
        | Render Summary
        |--------------------------------------------------------------------------
        */

        $this->renderSummary(
            $totalKeys,
            $locales
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Translate Text
    |--------------------------------------------------------------------------
    */

    protected function translate(
        string $text,
        string $locale
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Prevent Empty Values
        |--------------------------------------------------------------------------
        */

        if (empty(trim($text))) {
            return $text;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent Rate Limits
        |--------------------------------------------------------------------------
        */

        usleep(300000);

        /*
        |--------------------------------------------------------------------------
        | Google Translate
        |--------------------------------------------------------------------------
        */

        try {

            $response = Http::timeout(30)
                ->get(
                    'https://translate.googleapis.com/translate_a/single',
                    [
                        'client' => 'gtx',
                        'sl' => 'en',
                        'tl' => $locale,
                        'dt' => 't',
                        'q' => $text,
                    ]
                );

            if ($response->successful()) {

                $data = $response->json();

                $translated = $data[0][0][0] ?? null;

                if (! empty($translated)) {

                    return $translated;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        /*
        |--------------------------------------------------------------------------
        | LibreTranslate
        |--------------------------------------------------------------------------
        */

        try {

            $response = Http::timeout(30)
                ->post(
                    'https://libretranslate.com/translate',
                    [
                        'q' => $text,
                        'source' => 'en',
                        'target' => $locale,
                        'format' => 'text',
                    ]
                );

            if ($response->successful()) {

                $translated = $response->json()['translatedText']
                    ?? null;

                if (! empty($translated)) {

                    return $translated;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        /*
        |--------------------------------------------------------------------------
        | Lingva Translate
        |--------------------------------------------------------------------------
        */

        try {

            $url =
                "https://lingva.ml/api/v1/en/" .
                $locale .
                "/" .
                urlencode($text);

            $response = Http::timeout(30)
                ->get($url);

            if ($response->successful()) {

                $translated = $response->json()['translation']
                    ?? null;

                if (! empty($translated)) {

                    return $translated;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        return $text;
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

        $this->line(
            '  <fg=magenta;options=bold>' .
                $line .
                '</>'
        );

        $this->line(
            '  <fg=cyan;options=bold>  Laravel Translation Scanner</>'
        );

        $this->line(
            '  <fg=default>  by </>' .
                '<fg=magenta;options=bold>NativeCode</>' .
                '<fg=default> . </>' .
                '<fg=blue>https://nativecode.in</>'
        );

        $this->line(
            '  <fg=magenta;options=bold>' .
                $line .
                '</>'
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Summary Table & Footer
    |--------------------------------------------------------------------------
    */

    private function renderSummary(
        int $totalKeys,
        array $locales
    ): void {

        $this->line(
            '  <fg=cyan;options=bold>Translation Summary</>'
        );

        $this->line(
            '  <fg=magenta>' .
                str_repeat('-', 62) .
                '</>'
        );

        $this->table(
            [
                '<fg=cyan;options=bold>Metric</>',
                '<fg=cyan;options=bold>Value</>',
            ],
            [
                ['Locales translated', implode(', ', $locales)],
                ['Translation keys', $totalKeys],
            ]
        );

        $this->line(
            '  <fg=green;options=bold>[OK]</> All translations completed successfully.'
        );

        $this->newLine();

        $this->line(
            '  <fg=magenta>' .
                str_repeat('-', 62) .
                '</>'
        );

        $this->line(
            '  <fg=default>  Built with care by </>' .
                '<fg=magenta;options=bold>NativeCode</>' .
                '<fg=default> . </>' .
                '<fg=blue>https://nativecode.in</>'
        );

        $this->line(
            '  <fg=magenta>' .
                str_repeat('-', 62) .
                '</>'
        );

        $this->newLine();
    }
}
