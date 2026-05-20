<?php

namespace NativeCode\TranslationScanner;

use Illuminate\Support\ServiceProvider;
use NativeCode\TranslationScanner\Commands\ScanTranslationsCommand;

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
