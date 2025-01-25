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

    protected ?string $primaryKeyPrefix = null;

    protected bool $shouldCaptureAllAttributes = false;

    protected bool $shouldCaptureHiddenAttributes = false;

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
     * Specify the primary key alias for snapshot. If null, the model's name is prefixed by default.
     * In the following format: snake_cased_mode_name_ + Original key name
     *
     * This is done to prevent conflicts on the Snapshot model as the snapshot
     * model might use the same primary key attribute name as the main model.
     *
     * @param  string|null $with An underline is appended
     * @return $this
     */
    public function prefixPrimaryKey(?string $with): static
    {
        $this->primaryKeyPrefix = $with . "_";

        return $this;
    }

    /**
     * Capture all model attributes.
     *
     * @param  bool $shouldCaptureHiddenAttributes
     * @return $this
     */
    public function captureAll(bool $shouldCaptureHiddenAttributes = false): static
    {
        $this->shouldCaptureAllAttributes = true;

        $this->shouldCaptureHiddenAttributes = $shouldCaptureHiddenAttributes;

        return $this;
    }

    /**
     * Determine if hidden attributes should be captured.
     *
     * @param  bool $shouldCaptureAllAttributes
     * @return $this
     */
    public function captureHiddenAttributes(bool $shouldCaptureAllAttributes = true): static
    {
        $this->shouldCaptureHiddenAttributes = $shouldCaptureAllAttributes;

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

    public function getPrimaryKeyPrefix(): ?string
    {
        return $this->primaryKeyPrefix;
    }

    public function shouldCaptureAllAttributes(): bool
    {
        return $this->shouldCaptureAllAttributes;
    }

    public function shouldCaptureHiddenAttributes(): bool
    {
        return $this->shouldCaptureHiddenAttributes;
    }
}