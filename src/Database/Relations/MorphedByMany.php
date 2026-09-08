<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * MorphedByMany — the inverse of MorphToMany.
 *
 * Where MorphToMany is used on the owning model (Post → tags),
 * MorphedByMany is used on the related model (Tag → posts, videos, …).
 *
 * Example:
 *   Tag morphedByMany Post via 'taggables' pivot:
 *     taggable_id   → post ID
 *     taggable_type → 'App\Models\Post'
 *     tag_id        → this tag's ID
 *
 * Usage in Tag model:
 *   public function posts(): MorphedByMany
 *   {
 *       return $this->morphedByMany(Post::class, 'taggable');
 *   }
 */
class MorphedByMany extends Relation
{
    /**
     * Create a new MorphedByMany relation instance.
     *
     * @param QueryBuilder $query            Builder scoped to the related (owner) table.
     * @param Model        $parent           The "owned" model, i.e. the Tag.
     * @param Model        $related          The owning model (e.g. Post).
     * @param string       $table            The pivot/intermediate table name (e.g. 'taggables').
     * @param string       $foreignPivotKey  FK on pivot pointing to $parent (e.g. 'tag_id').
     * @param string       $relatedPivotKey  FK on pivot pointing to $related (e.g. 'taggable_id').
     * @param string       $morphType        Morph type column on pivot (e.g. 'taggable_type').
     * @param string       $parentKey        PK on the parent (Tag) table (default 'id').
     * @param string       $relatedKey       PK on the related (Post) table (default 'id').
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

        // The related class (e.g. Post) determines the morph type value.
        $relatedClass = get_class($this->related);
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
            ->where("{$this->table}.{$this->morphType}", '=', $relatedClass)
            ->get();

        return array_map(fn ($row) => $this->related->newFromBuilder($row), $rows);
    }

    /**
     * Attach owning models to this morphable model via the pivot table.
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

        $relatedClass = get_class($this->related);
        $ids          = (array) $ids;
        $db           = $this->query->getConnection();

        foreach ($ids as $id) {
            $record = array_merge([
                $this->foreignPivotKey => $parentKeyValue,
                $this->relatedPivotKey => $id,
                $this->morphType       => $relatedClass,
            ], $attributes);

            $db->table($this->table)->insert($record);
        }
    }

    /**
     * Detach owning models from the pivot table.
     *
     * @param int|string|array<int|string>|null $ids
     */
    public function detach(int|string|array|null $ids = null): int
    {
        $parentKeyValue = $this->parent->getAttribute($this->parentKey);
        if ($parentKeyValue === null) {
            return 0;
        }

        $relatedClass = get_class($this->related);
        $db           = $this->query->getConnection();

        $q = $db->table($this->table)
            ->where($this->foreignPivotKey, '=', $parentKeyValue)
            ->where($this->morphType, '=', $relatedClass);

        if ($ids !== null) {
            $q->whereIn($this->relatedPivotKey, (array) $ids);
        }

        return $q->delete();
    }
}
