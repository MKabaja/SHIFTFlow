<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property bool $is_important
 * @property int|null $author_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $author
 *
 * @method static \Database\Factories\NewsPostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereIsImportant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsPost whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NewsPost extends Model
{
    /** @use HasFactory<NewsPostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'is_important',
        'author_id',
    ];

    protected $attributes = [
        'is_important' => false,
    ];

    protected $casts = [
        'is_important' => 'boolean',
    ];

    /** @return BelongsTo<User,$this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
