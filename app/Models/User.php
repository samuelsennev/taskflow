<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $github_id
 * @property int|null $telegram_chat_id
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'github_id', 'password'])]
#[Hidden(['password', 'remember_token', 'telegram_chat_id'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'telegram_chat_id' => 'integer',
        ];
    }

    /**
     * Determine if the user has linked a Telegram chat to their account.
     */
    public function hasTelegramConnected(): bool
    {
        return $this->telegram_chat_id !== null;
    }

    /**
     * Determine if an MCP client (e.g. Claude) holds a live OAuth token for the account.
     */
    public function hasMcpClientConnected(): bool
    {
        return $this->tokens()->where('revoked', false)->where('expires_at', '>', now())->exists();
    }

    /**
     * Revoke every OAuth token issued to the account, cutting off all MCP clients.
     */
    public function revokeMcpAccess(): void
    {
        $this->tokens()->with('refreshToken')->where('revoked', false)->each(function (Token $token): void {
            $token->revoke();
            $token->refreshToken?->revoke();
        });
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
