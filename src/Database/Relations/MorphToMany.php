<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * MorphToMany — many-to-many polymorphic relationship.
 *
 * Like BelongsToMany but the pivot table also stores a morph type column so
 * multiple model types can share the same pivot table.
 *
 * Example:
 *   Post/Video both share a "taggables" pivot table:
 *     taggable_id   → 1
 *     taggable_type → 'App\Models\Post'
 *     tag_id        → 3
 *
 * Usage in Post model:
 *   public function tags(): MorphToMany
 *   {
 *       return $this->morphToMany(Tag::class, 'taggable');
 *   }
 */
class MorphToMany extends Relation
{
    /**
     * Create a new MorphToMany relation instance.
     *
     * @param QueryBuilder $query            Builder scoped to the related table.
     * @param Model        $parent           The owning model (e.g. Post).
     * @param Model        $related          The related model (e.g. Tag).
     * @param string       $table            The pivot/intermediate table name (e.g. 'taggables').
     * @param string       $foreignPivotKey  FK on pivot referencing the owner (e.g. 'taggable_id').
     * @param string       $relatedPivotKey  FK on pivot referencing the related model (e.g. 'tag_id').
     * @param string       $morphType        Morph type column on the pivot (e.g. 'taggable_type').
     * @param string       $parentKey        PK on the parent table (default 'id').
     * @param string       $relatedKey       PK on the related table (default 'id').
     */
    public function __construct(
        QueryBuilder $query,
        Model $parent,
        Model $related,
        protected string $table,
        protected string $foreignPivotKey,
        protected string $relatedPivotKey,
        protected string $morphType,
        protected string $parentKey = 'id',
        protected string $relatedKey = 'id'
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
        $parentKeyValue = $this->parent->getAttribute($this->parentKey);
        if ($parentKeyValue === null) {
            return [];
        }

        $ownerClass   = get_class($this->parent);
        $relatedTable = $this->related->getTable();

        $rows = $this->query
            ->select("{$relatedTable}.*")
            ->join(
                $this->table,
                "{$this->table}.{$this->relatedPivotKey}",
                '=',
                "{$relatedTable}.{$this->relatedKey}"
            )
            ->where("{$this->table}.{$this->foreignPivotKey}", '=', $parentKeyValue)
            ->where("{$this->table}.{$this->morphType}", '=', $ownerClass)
            ->get();

        return array_map(fn ($row) => $this->related->newFromBuilder($row), $rows);
    }

    /**
     * Attach related models to the owner via the polymorphic pivot table.
     *
     * @param int|string|array<int|string> $ids
     * @param array<string, mixed>         $attributes  Extra pivot columns.
     */
    public function attach(int|string|array $ids, array $attributes = []): void
    {
        $parentKeyValue = $this->parent->getAttribute($this->parentKey);
        if ($parentKeyValue === null) {
            return;
        }

        $ownerClass = get_class($this->parent);
        $ids        = (array) $ids;
        $db         = $this->query->getConnection();

        foreach ($ids as $id) {
            $record = array_merge([
                $this->foreignPivotKey => $parentKeyValue,
                $this->relatedPivotKey => $id,
                $this->morphType       => $ownerClass,
            ], $attributes);

            $db->table($this->table)->insert($record);
        }
    }

    /**
     * Detach related models from the polymorphic pivot table.
     *
     * @param int|string|array<int|string>|null $ids
     */
    public function detach(int|string|array|null $ids = null): int
    {
        $parentKeyValue = $this->parent->getAttribute($this->parentKey);
        if ($parentKeyValue === null) {
            return 0;
        }

        $ownerClass = get_class($this->parent);
        $db         = $this->query->getConnection();

        $q = $db->table($this->table)
            ->where($this->foreignPivotKey, '=', $parentKeyValue)
            ->where($this->morphType, '=', $ownerClass);

        if ($ids !== null) {
            $q->whereIn($this->relatedPivotKey, (array) $ids);
        }

        return $q->delete();
    }

    /**
     * Sync the polymorphic pivot table with the given list of IDs.
     *
     * @param array<int|string> $ids
     * @return array{attached: array<int|string>, detached: array<int|string>}
     */
    public function sync(array $ids): array
    {
        $parentKeyValue = $this->parent->getAttribute($this->parentKey);
        if ($parentKeyValue === null) {
            return ['attached' => [], 'detached' => []];
        }

        $ownerClass = get_class($this->parent);
        $db         = $this->query->getConnection();

        $current    = $db->table($this->table)
            ->where($this->foreignPivotKey, '=', $parentKeyValue)
            ->where($this->morphType, '=', $ownerClass)
            ->get();

        $currentIds = array_column($current, $this->relatedPivotKey);
        $detachIds  = array_diff($currentIds, $ids);
        $attachIds  = array_diff($ids, $currentIds);

        if (!empty($detachIds)) {
            $this->detach(array_values($detachIds));
        }

        foreach ($attachIds as $id) {
            $this->attach($id);
        }

        return [
            'attached' => array_values($attachIds),
            'detached' => array_values($detachIds),
        ];
    }
}
