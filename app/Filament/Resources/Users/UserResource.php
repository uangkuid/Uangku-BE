<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\Users\RelationManagers\WalletsRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Helpers\EncryptionHelper;
use App\Helpers\StorageHelper;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $recordTitleAttribute = 'User';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('staffsus/navigation.groups.users');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Flex::make([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('id')
                                        ->label(__('staffsus/users.fields.user_id'))
                                        ->copyable()
                                        ->copyMessage('Copied!')
                                        ->copyMessageDuration(1500),
                                    TextEntry::make('name')
                                        ->label(__('staffsus/users.fields.name')),
                                    TextEntry::make('email')
                                        ->label(__('staffsus/users.fields.email'))
                                        ->getStateUsing(fn ($record) => EncryptionHelper::decryptEmail($record->email)),
                                    TextEntry::make('email_verified_at')
                                        ->label(__('staffsus/users.fields.email_status'))
                                        ->badge()
                                        ->getStateUsing(fn ($record) => $record->email_verified_at ? __('staffsus/users.fields.verified') : __('staffsus/users.fields.unverified'))
                                        ->color(fn (string $state) => match ($state) {
                                            __('staffsus/users.fields.verified') => 'success',
                                            __('staffsus/users.fields.unverified') => 'danger',
                                            default => 'gray',
                                        }),
                                    TextEntry::make('status')
                                        ->label(__('staffsus/users.fields.account_status'))
                                        ->badge()
                                        ->getStateUsing(fn ($record) => ($record->status ?? UserStatus::Active)->label())
                                        ->color(fn ($record) => ($record->status ?? UserStatus::Active)->color()),
                                    TextEntry::make('suspended_reason')
                                        ->label(__('staffsus/users.fields.suspension_reason'))
                                        ->placeholder(__('staffsus/users.fields.placeholder_empty'))
                                        ->visible(fn ($record) => filled($record->suspended_reason)),
                                ]),
                                Group::make([
                                    TextEntry::make('created_at')
                                        ->label(__('staffsus/users.fields.joined_at'))
                                        ->dateTime(),
                                    TextEntry::make('updated_at')
                                        ->label(__('staffsus/users.fields.updated_at'))
                                        ->since(),
                                ]),
                            ])
                            ->grow(true),
                        ImageEntry::make('avatar')
                            ->disk('minio')
                            ->visibility('private')
                            ->hiddenLabel()
                            ->circular()
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
                            ->grow(false),
                    ])->from('lg'),
                ])->columnSpanFull(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            WalletsRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            //            'create' => CreateUser::route('/create'),
            //            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
