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

    protected bool $shouldCaptureCasts = true;

    protected bool $shouldExcludeTimestamps = false;

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

    public function captureCasts(bool $shouldCaptureCasts = true): static
    {
        $this->shouldCaptureCasts = $shouldCaptureCasts;

        return $this;
    }

    public function excludeTimestamps(bool $shouldExcludeTimestamps = true): static
    {
        $this->shouldExcludeTimestamps = $shouldExcludeTimestamps;

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

    public function shouldCaptureCasts(): bool
    {
        return $this->shouldCaptureCasts;
    }

    public function shouldExcludeTimestamps(): bool
    {
        return $this->shouldExcludeTimestamps;
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