<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * MorphMany — one-to-many polymorphic relationship.
 *
 * Example: Post hasMany Comment (morphable), where comments table has:
 *   commentable_type → 'App\Models\Post'
 *   commentable_id   → 1
 *
 * Usage in Post model:
 *   public function comments(): MorphMany
 *   {
 *       return $this->morphMany(Comment::class, 'commentable');
 *   }
 */
class MorphMany extends Relation
{
    /**
     * Create a new MorphMany relation instance.
     *
     * @param QueryBuilder $query      Builder scoped to the related (morphable) table.
     * @param Model        $parent     The owning model (e.g. Post).
     * @param Model        $related    The related morphable model (e.g. Comment).
     * @param string       $morphType  Column holding the owner class name (e.g. 'commentable_type').
     * @param string       $morphId    Column holding the owner primary key (e.g. 'commentable_id').
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
     *
     * @return array<Model>
     */
    public function getResults(): array
    {
        $parentKeyValue = $this->parent->getAttribute($this->localKey);
        if ($parentKeyValue === null) {
            return [];
        }

        $ownerClass = get_class($this->parent);

        $rows = $this->query
            ->where($this->morphType, '=', $ownerClass)
            ->where($this->morphId, '=', $parentKeyValue)
            ->get();

        return array_map(fn ($row) => $this->related->newFromBuilder($row), $rows);
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

    /**
     * Create multiple related models at once.
     *
     * @param array<array<string, mixed>> $records
     * @return array<Model>
     */
    public function createMany(array $records): array
    {
        return array_map(fn ($attrs) => $this->create($attrs), $records);
    }
}
