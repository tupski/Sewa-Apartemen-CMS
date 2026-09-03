<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', self::class)
            ->withPivot('model_type');
    }

    /**
     * Whether the user holds any of the given role slugs.
     */
    public function hasRole(string|array $slugs): bool
    {
        $slugs = (array) $slugs;

        return $this->roles->pluck('slug')->intersect($slugs)->isNotEmpty();
    }

    /**
     * Whether the user may access the admin panel.
     *
     * BUG-025 FIX: Dukung beberapa role admin agar tidak hardcode hanya 'super-admin'.
     * 'super-admin' tetap role utama, 'admin' adalah alias yang diizinkan.
     * Untuk menambah role baru cukup tambahkan ke array ini.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['super-admin', 'admin']);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Generate a Gravatar URL for the user.
     */
    public function avatarUrl(int $size = 80): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mm";
    }

    /**
     * Generate a short name for the user.
     */
    public function shortName(): string
    {
        return Str::limit($this->name, 20);
    }

    /**
     * Initials used by the admin header avatar when no photo is uploaded.
     *
     * Takes the first letter of the first and last word ("Lya Rooms" => "LR"),
     * falling back to the first letter of the email when the name is blank.
     */
    public function initials(int $max = 2): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return Str::upper(Str::substr((string) $this->email, 0, 1)) ?: '?';
        }

        if (count($words) === 1) {
            return Str::upper(Str::substr($words[0], 0, 1));
        }

        $picked = array_merge([reset($words)], array_slice($words, -($max - 1)));

        return Str::upper(implode('', array_map(
            fn (string $word): string => Str::substr($word, 0, 1),
            array_slice($picked, 0, $max)
        )));
    }
}
