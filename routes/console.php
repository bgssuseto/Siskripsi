<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reclaim server storage: drop uploaded persyaratan PDFs once they're over a
// year old. Only the physical file is removed — the Sidang record and every
// other field on it are left untouched.
Schedule::command('app:cleanup-old-persyaratan-files')->daily();
