<?php

namespace App\Actions\Services;

use App\Models\Service;

class ToggleServiceActiveAction
{
    public function execute(Service $service): Service
    {
        $service->update(['is_active' => ! $service->is_active]);

        return $service->fresh();
    }
}
