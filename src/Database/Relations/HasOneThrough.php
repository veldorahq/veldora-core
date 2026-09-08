<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * HasOneThrough — reach a distant model through an intermediate pivot table row.
 *
 * Example:
 *   Country  --(hasOneThrough)--> User  --(through)--> Profile
 *
 *   $country->profile()
 *     = HasOneThrough(
 *         related:      Profile,
 *         through:      User,
 *         firstKey:     'country_id',   // on users
 *         secondKey:    'user_id',      // on profiles
 *         localKey:     'id',           // on countries
 *         secondLocalKey: 'id'          // on users
 *       )
 */
class HasOneThrough extends Relation
{
    /**
     * Create a new HasOneThrough relation instance.
     *
     * @param QueryBuilder $query        Builder scoped to the related (far) table.
     * @param Model        $parent       The "has" side (e.g. Country).
     * @param Model        $related      The far model (e.g. Profile).
     * @param Model        $through      The intermediate model (e.g. User).
     * @param string       $firstKey     FK on the through table pointing to the parent (e.g. 'country_id').
     * @param string       $secondKey    FK on the related table pointing to the through table (e.g. 'user_id').
     * @param string       $localKey     PK on the parent table (default 'id').
     * @param string       $secondLocalKey PK on the through table (default 'id').
     */
    public function __construct(
        QueryBuilder $query,
        Model $parent,
        Model $related,
        protected Model $through,
        protected string $firstKey,
        protected string $secondKey,
        protected string $localKey = 'id',
        protected string $secondLocalKey = 'id'
    ) {
        parent::__construct($query, $parent, $related);
    }

    /**
     * Resolve the relationship results.
     *
     * Performs a JOIN between the related table and the through table so a
     * single SQL query is used instead of two round-trips.
     */
    public function getResults(): ?Model
    {
        $parentKeyValue = $this->parent->getAttribute($this->localKey);
        if ($parentKeyValue === null) {
            return null;
        }

        $throughTable  = $this->through->getTable();
        $relatedTable  = $this->related->getTable();

        // JOIN related ON related.secondKey = through.secondLocalKey
        // WHERE through.firstKey = parentKeyValue
        $row = $this->query
            ->select("{$relatedTable}.*")
            ->join(
                $throughTable,
                "{$throughTable}.{$this->secondLocalKey}",
                '=',
                "{$relatedTable}.{$this->secondKey}"
            )
            ->where("{$throughTable}.{$this->firstKey}", '=', $parentKeyValue)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->related->newFromBuilder($row);
    }
}
