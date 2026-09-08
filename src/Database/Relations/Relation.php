<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Relations;

use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;

abstract class Relation
{
    /**
     * Create a new Relation instance.
     */
    public function __construct(
        protected QueryBuilder $query,
        protected Model $parent,
        protected Model $related
    ) {
    }

    /**
     * Resolve the relationship results.
     */
    abstract public function getResults(): mixed;

    /**
     * Alias for getResults() — enables $model->relation()->get() syntax.
     */
    public function get(): mixed
    {
        return $this->getResults();
    }

    /**
     * Proxy any QueryBuilder method calls through the relation's query.
     *
     * Enables: $post->comments()->where('approved', '=', 1)->count()
     *
     * @param array<mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->{$method}(...$parameters);

        // If the builder returns itself (method-chaining), return $this so
        // the caller can keep chaining relation methods too.
        return $result === $this->query ? $this : $result;
    }
}
