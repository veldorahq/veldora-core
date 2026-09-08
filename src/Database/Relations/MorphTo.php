<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

/**
 * MorphTo — the inverse of a polymorphic relationship.
 *
 * A morphable model stores both a *type* column and an *id* column, e.g.:
 *
 *   comments
 *     commentable_type  → 'App\Models\Post'
 *     commentable_id    → 5
 *
 * Usage in Comment model:
 *   public function commentable(): MorphTo
 *   {
 *       return $this->morphTo('commentable');
 *   }
 *
 * Calling $comment->commentable()->getResults() fetches the owner.
 */
class MorphTo extends Relation
{
    /**
     * Create a new MorphTo relation instance.
     *
     * @param QueryBuilder $query         Builder (not used for the actual query here, kept for chaining).
     * @param Model        $parent        The morphable child model (e.g. Comment).
     * @param Model        $related       A placeholder — the real related model is resolved at runtime.
     * @param string       $morphType     Column holding the owner class name (e.g. 'commentable_type').
     * @param string       $morphId       Column holding the owner primary key (e.g. 'commentable_id').
     * @param string       $ownerKey      The primary key column on the owner model (default 'id').
     */
    public function __construct(
        QueryBuilder $query,
        Model $parent,
        Model $related,
        protected string $morphType,
        protected string $morphId,
        protected string $ownerKey = 'id'
    ) {
        parent::__construct($query, $parent, $related);
    }

    /**
     * Resolve the relationship — instantiates the real owner class dynamically.
     */
    public function getResults(): ?Model
    {
        $ownerClass = $this->parent->getAttribute($this->morphType);
        $ownerId    = $this->parent->getAttribute($this->morphId);

        if ($ownerClass === null || $ownerId === null) {
            return null;
        }

        if (!class_exists($ownerClass)) {
            return null;
        }

        /** @var Model $ownerModel */
        $ownerModel = new $ownerClass();
        $db = $this->query->getConnection();

        $row = $db->table($ownerModel->getTable())
            ->where($this->ownerKey, '=', $ownerId)
            ->first();

        if ($row === null) {
            return null;
        }

        return $ownerModel->newFromBuilder($row);
    }
}
