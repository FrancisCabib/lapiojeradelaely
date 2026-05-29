<?php

namespace App\Actions\Services;

use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ReorderServicesAction
{
    /**
     * @param  array<int, int>  $order  Map of service_id => sort_order
     */
    public function execute(array $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order as $serviceId => $sortOrder) {
                Service::where('id', $serviceId)->update(['sort_order' => $sortOrder]);
            }
        });
    }
}
