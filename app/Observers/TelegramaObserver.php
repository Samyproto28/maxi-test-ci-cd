<?php

namespace App\Observers;

use App\Models\Telegrama;
use App\Services\ResultadoCalculationService;

class TelegramaObserver
{
    protected ResultadoCalculationService $calculationService;

    public function __construct(ResultadoCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function created(Telegrama $telegrama): void
    {
        $this->calculationService->invalidarCaches();
    }

    public function updated(Telegrama $telegrama): void
    {
        $this->calculationService->invalidarCaches();
    }

    public function deleted(Telegrama $telegrama): void
    {
        $this->calculationService->invalidarCaches();
    }
}
