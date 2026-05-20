<?php

namespace NativeCodeIn\TranslationScanner;

use Illuminate\Support\ServiceProvider;
use NativeCodeIn\TranslationScanner\Commands\ScanTranslationsCommand;
use NativeCodeIn\TranslationScanner\Commands\TranslateJsonCommand;

class TranslationScannerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->commands([
            ScanTranslationsCommand::class,
            TranslateJsonCommand::class,
        ]);
    }
}
