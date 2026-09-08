<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * MorphOne — one-to-one polymorphic relationship.
 *
 * Example: Post hasOne Image (morphable), where images table has:
 *   imageable_type → 'App\Models\Post'
 *   imageable_id   → 1
 *
 * Usage in Post model:
 *   public function image(): MorphOne
 *   {
 *       return $this->morphOne(Image::class, 'imageable');
 *   }
 */
class MorphOne extends Relation
{
    /**
     * Create a new MorphOne relation instance.
     *
     * @param QueryBuilder $query      Builder scoped to the related (morphable) table.
     * @param Model        $parent     The owning model (e.g. Post).
     * @param Model        $related    The related morphable model (e.g. Image).
     * @param string       $morphType  Column holding the owner class name (e.g. 'imageable_type').
     * @param string       $morphId    Column holding the owner primary key (e.g. 'imageable_id').
     * @param string       $localKey   Primary key on the parent model (default 'id').
     */
    public function __construct(
        QueryBuilder $query,
        Model $parent,
        Model $related,
        protected string $morphType,
        protected string $morphId,
        protected string $localKey = 'id'
    ) {
        parent::__construct($query, $parent, $related);
    }

    /**
     * Resolve the relationship results.
     */
    public function getResults(): ?Model
    {
        $parentKeyValue = $this->parent->getAttribute($this->localKey);
        if ($parentKeyValue === null) {
            return null;
        }

        $ownerClass = get_class($this->parent);

        $row = $this->query
            ->where($this->morphType, '=', $ownerClass)
            ->where($this->morphId, '=', $parentKeyValue)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->related->newFromBuilder($row);
    }

    /**
     * Create and persist a new related model tied to this polymorphic owner.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes = []): Model
    {
        $parentKeyValue = $this->parent->getAttribute($this->localKey);
        $ownerClass     = get_class($this->parent);

        $attributes[$this->morphType] = $ownerClass;
        $attributes[$this->morphId]   = $parentKeyValue;

        return $this->related::create($attributes);
    }
}
