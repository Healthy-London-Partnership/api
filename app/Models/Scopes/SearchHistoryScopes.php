<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait SearchHistoryScopes
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFilledQuery(Builder $query): Builder
    {
        /**
         * Look for legacy non-function_score path and function_score path.
         */
        return $query->whereNotNull(
            DB::raw(
                $this->ifNull(
                    'JSON_EXTRACT(`query`, "$.query.function_score.query.bool.must.bool.should[0].match_phrase.name.query")',
                    $this->ifNull(
                        'JSON_EXTRACT(`query`, "$.query.function_score.query.bool.must.bool.should[0].match.name.query")',
                        'JSON_EXTRACT(`query`, "$.query.bool.must.bool.should[0].match.name.query")'
                    )
                )
            )
        );
    }

    protected function ifNull(string $field, string $default): string
    {
        return sprintf('IFNULL(%s, %s)', $field, $default);
    }
}
