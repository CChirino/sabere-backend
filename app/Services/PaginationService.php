<?php

namespace App\Services;

use Illuminate\Http\Request;

class PaginationService
{
    public function perPage(Request $request, int $default = 15, int $max = 50): int
    {
        return min($request->integer('per_page', $default), $max);
    }
}
