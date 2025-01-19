<?php

namespace Dvarilek\LaravelSnapshotTree\ValueObjects;

class SnapshotDefinition
{

    /**
     * @var list<string>
     */
    protected array $attributes = [];

    /**
     * @var list<string>
     */
    protected array $excludedAttributes = [];

    protected bool $shouldCaptureAllAttributes = false;

    /**
     * @var list<RelationDefinition>
     */
    protected array $relations = [];

    public static function make(): static
    {
        return new self();
    }

    /**
     * Capture specific model attributes.
     *
     * @param  list<string> $attributes
     * @return $this
     */
    public function capture(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Exclude specific model attributes.
     *
     * @param  list<string> $attributes
     * @return $this
     */
    public function exclude(array $attributes): static
    {
        $this->excludedAttributes = $attributes;

        return $this;
    }

    /**
     * Define a snapshot of attributes on a related model.
     *
     * @param  list<RelationDefinition> $relations
     * @return static
     */
    public function captureRelations(array $relations): static
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Capture all model attributes.
     *
     * @return $this
     */
    public function captureAll(): static
    {
        $this->shouldCaptureAllAttributes = true;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCapturedAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return list<string>
     */
    public function getExcludedAttributes(): array
    {
        return $this->excludedAttributes;
    }

    /**
     * @return list<RelationDefinition>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    public function shouldCaptureAllAttributes(): bool
    {
        return $this->shouldCaptureAllAttributes;
    }
}