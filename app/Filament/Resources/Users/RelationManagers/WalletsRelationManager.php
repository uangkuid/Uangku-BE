<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\WalletAccess;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletAccess';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === 'App\Filament\Resources\Users\Pages\ViewUser';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('staffsus/users.relation_managers.wallets.sections.wallet_information'))
                            ->schema([
                                Placeholder::make('wallet.id')
                                    ->label(__('staffsus/users.relation_managers.wallets.fields.wallet_id'))
                                    ->content(fn (WalletAccess $record): ?string => $record->wallet?->id)
                                    ->columnSpanFull(),
                                Placeholder::make('wallet.type')
                                    ->label(__('staffsus/users.relation_managers.wallets.fields.type'))
                                    ->content(fn (WalletAccess $record): ?string => $record->wallet?->type),
                                Placeholder::make('wallet.amount')
                                    ->label(__('staffsus/users.relation_managers.wallets.fields.amount'))
                                    ->content(__('staffsus/users.relation_managers.wallets.fields.encrypted_zero_knowledge')),
                                Placeholder::make('role')
                                    ->label(__('staffsus/users.relation_managers.wallets.fields.role'))
                                    ->content(fn (WalletAccess $record): ?string => $record->role),
                                Placeholder::make('is_active')
                                    ->label(__('staffsus/users.relation_managers.wallets.fields.status'))
                                    ->content(fn (WalletAccess $record): string => $record->is_active ? __('staffsus/users.relation_managers.wallets.fields.active') : __('staffsus/users.relation_managers.wallets.fields.inactive')),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?WalletAccess $record) => $record === null ? 3 : 2]),
                Section::make(__('staffsus/users.relation_managers.wallets.sections.general_information'))
                    ->schema([
                        Placeholder::make('created_at')
                            ->label(__('staffsus/users.relation_managers.wallets.fields.created_at'))
                            ->content(fn (WalletAccess $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label(__('staffsus/users.relation_managers.wallets.fields.updated_at'))
                            ->content(fn (WalletAccess $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?WalletAccess $record) => $record === null),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('wallet.id')
            ->columns([
                TextColumn::make('wallet.id')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.wallet_id'))
                    ->searchable(),
                TextColumn::make('wallet.type')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.type'))
                    ->badge(),
                TextColumn::make('wallet.amount')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.amount'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => __('staffsus/users.relation_managers.wallets.fields.encrypted_short'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Member' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('staffsus/users.relation_managers.wallets.fields.active') : __('staffsus/users.relation_managers.wallets.fields.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.role'))
                    ->options([
                        'Admin' => 'Admin',
                        'Member' => 'Member',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('staffsus/users.relation_managers.wallets.fields.status'))
                    ->boolean()
                    ->trueLabel(__('staffsus/users.relation_managers.wallets.fields.active'))
                    ->falseLabel(__('staffsus/users.relation_managers.wallets.fields.inactive'))
                    ->native(false),
            ])
            ->headerActions([
                // No create/edit actions - read only
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions - read only
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
