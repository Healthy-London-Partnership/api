<?php

namespace App\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueSlugGenerator
{
    /**
     * @param string $slug
     * @param string $table
     * @param string $column
     * @return string
     */
    public function generate(string $slug, string $table, string $column = 'slug'): string
    {
        $index = 0;
        do {
            $slugCopy = Str::slug($slug);
            $slugCopy .= $index === 0 ? '' : "-{$index}";

            $slugAlreadyUsed = DB::table($table)
                ->where($column, '=', $slugCopy)
                ->exists();

            if ($slugAlreadyUsed) {
                $index++;
                continue;
            }

            return $slugCopy;
        } while (true);
    }
}
