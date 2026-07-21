<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel\Eloquent;

use BradieTilley\Snowflake\Laravel\SnowflakeGenerator;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property-read string $id Snowflake ID e.g. "5335794106177873665"
 */
trait HasSnowflake
{
    public static function bootHasSnowflake(): void
    {
        static::creating(function (Model $model) {
            if (! $model->id) {
                $model->forceFill([
                    'id' => static::getNextSnowflakeId(),
                ]);
            }
        });
    }

    public function initializeHasSnowflake(): void
    {
        $this->mergeCasts([
            'id' => 'string',
        ]);

        $this->setIncrementing(false);
        $this->setKeyType('string');
    }

    public static function getNextSnowflakeId(): string
    {
        return SnowflakeGenerator::make()->id(static::class);
    }
}
