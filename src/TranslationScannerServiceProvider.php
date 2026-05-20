<?php

namespace NativeCodeIn\TranslationScanner;

use Illuminate\Support\ServiceProvider;
use NativeCodeIn\TranslationScanner\Commands\ScanTranslationsCommand;

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
        ]);
    }
}
