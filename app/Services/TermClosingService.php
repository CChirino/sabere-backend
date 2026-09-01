<?php

namespace App\Services;

use App\Models\StudentScore;
use App\Models\Term;

class TermClosingService
{
    /**
     * Cerrar un lapso y marcar sus notas como finales.
     */
    public static function close(Term $term): int
    {
        $term->update(['is_closed' => true]);

        $affected = StudentScore::where('term_id', $term->id)
            ->where('is_final', false)
            ->update(['is_final' => true]);

        return $affected;
    }
}
