<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatus;
use App\Helpers\EncryptionHelper;
use App\Helpers\StorageHelper;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserKey;
use App\Models\UserSeasons;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('staffsus/users.fields.id'))
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('avatar')
                    ->disk('minio')
                    ->visibility('private')
                    ->getStateUsing(function ($record) {
                        if ($record->avatar) {
                            return StorageHelper::temporaryUrl(
                                'minio',
                                "avatar/{$record->id}/{$record->avatar}",
                                now()->addMinutes(60)
                            );
                        }

                        return null;
                    })
                    ->imageSize(40)
                    ->circular()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('staffsus/users.fields.email'))
                    ->formatStateUsing(fn ($state) => EncryptionHelper::decryptFromString($state, EncryptionHelper::getSystemSecretKey()))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Cek apakah input merupakan email valid
                        if (! filter_var($search, FILTER_VALIDATE_EMAIL)) {
                            return $query; // Skip filter, biar nggak error dan tetap bisa search lainnya
                        }

                        $staticIv = env('MAIN_STATIC_IV') ?? throw new \Exception('Static IV not found!');

                        return $query->where('email', EncryptionHelper::encryptAsString(
                            data: $search,
                            key: EncryptionHelper::getSystemSecretKey(),
                            iv: $staticIv,
                        ));
                    })
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('staffsus/users.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?UserStatus $state) => ($state ?? UserStatus::Active)->label())
                    ->color(fn (?UserStatus $state) => ($state ?? UserStatus::Active)->color())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->label(__('staffsus/users.fields.verified'))
                    ->dateTime()
                    ->placeholder(__('staffsus/users.fields.unverified'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('staffsus/users.fields.joined_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/users.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('staffsus/users.fields.status'))
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::suspendAction(),
                    self::unsuspendAction(),
                    self::banAction(),
                    self::verifyEmailAction(),
                    self::resetPinAction(),
                    self::forceLogoutAction(),
                ]),
            ]);
    }

    private static function can(string $permission): bool
    {
        return (bool) Filament::auth()->user()?->can($permission);
    }

    private static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label(__('staffsus/users.actions.suspend'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->visible(fn (User $record) => self::can('User:Suspend') && ($record->status ?? UserStatus::Active) === UserStatus::Active)
            ->schema([
                Textarea::make('reason')->label(__('staffsus/users.fields.reason'))->required()->maxLength(500),
            ])
            ->requiresConfirmation()
            ->action(function (User $record, array $data) {
                $record->update([
                    'status' => UserStatus::Suspended,
                    'suspended_at' => now(),
                    'suspended_reason' => $data['reason'],
                ]);
                UserSeasons::where('users', $record->id)->delete();
                AuditLog::record('user.suspend', $record, ['reason' => $data['reason']], 'Suspend user');
                Notification::make()->title(__('staffsus/users.notifications.suspended'))->success()->send();
            });
    }

    private static function unsuspendAction(): Action
    {
        return Action::make('unsuspend')
            ->label(__('staffsus/users.actions.unsuspend'))
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->visible(fn (User $record) => self::can('User:Suspend') && ($record->status ?? UserStatus::Active) !== UserStatus::Active)
            ->requiresConfirmation()
            ->action(function (User $record) {
                $record->update([
                    'status' => UserStatus::Active,
                    'suspended_at' => null,
                    'suspended_reason' => null,
                ]);
                AuditLog::record('user.unsuspend', $record, [], __('staffsus/users.audit_descriptions.unsuspend'));
                Notification::make()->title(__('staffsus/users.notifications.unsuspended'))->success()->send();
            });
    }

    private static function banAction(): Action
    {
        return Action::make('ban')
            ->label(__('staffsus/users.actions.ban'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (User $record) => self::can('User:Ban') && ($record->status ?? UserStatus::Active) !== UserStatus::Banned)
            ->schema([
                Textarea::make('reason')->label(__('staffsus/users.fields.reason'))->required()->maxLength(500),
            ])
            ->requiresConfirmation()
            ->action(function (User $record, array $data) {
                $record->update([
                    'status' => UserStatus::Banned,
                    'suspended_at' => now(),
                    'suspended_reason' => $data['reason'],
                ]);
                UserSeasons::where('users', $record->id)->delete();
                AuditLog::record('user.ban', $record, ['reason' => $data['reason']], 'Ban user');
                Notification::make()->title(__('staffsus/users.notifications.banned'))->success()->send();
            });
    }

    private static function verifyEmailAction(): Action
    {
        return Action::make('verifyEmail')
            ->label(__('staffsus/users.actions.verify_email'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (User $record) => self::can('User:VerifyEmail') && $record->email_verified_at === null)
            ->requiresConfirmation()
            ->action(function (User $record) {
                $record->update(['email_verified_at' => now()]);
                AuditLog::record('user.verify_email', $record, [], __('staffsus/users.audit_descriptions.verify_email'));
                Notification::make()->title(__('staffsus/users.notifications.email_verified'))->success()->send();
            });
    }

    private static function resetPinAction(): Action
    {
        return Action::make('resetPin')
            ->label(__('staffsus/users.actions.reset_pin'))
            ->icon('heroicon-o-key')
            ->color('warning')
            ->visible(fn () => self::can('User:ResetPin'))
            ->requiresConfirmation()
            ->modalDescription(__('staffsus/users.modals.reset_pin_description'))
            ->action(function (User $record) {
                UserKey::where('users', $record->id)->update(['hashed_pin' => null]);
                AuditLog::record('user.reset_pin', $record, [], 'Reset PIN transaksi');
                Notification::make()->title(__('staffsus/users.notifications.pin_reset'))->success()->send();
            });
    }

    private static function forceLogoutAction(): Action
    {
        return Action::make('forceLogout')
            ->label(__('staffsus/users.actions.force_logout'))
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->color('gray')
            ->visible(fn () => self::can('User:ForceLogout'))
            ->requiresConfirmation()
            ->modalDescription(__('staffsus/users.modals.force_logout_description'))
            ->action(function (User $record) {
                $count = UserSeasons::where('users', $record->id)->delete();
                AuditLog::record('user.force_logout', $record, ['revoked_sessions' => $count], 'Force logout');
                Notification::make()->title(__('staffsus/users.notifications.session_revoked'))->success()->send();
            });
    }
}
