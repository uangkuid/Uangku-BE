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
                        Section::make('Wallet Information')
                            ->schema([
                                Placeholder::make('wallet.id')
                                    ->label('Wallet ID')
                                    ->content(fn (WalletAccess $record): ?string => $record->wallet?->id)
                                    ->columnSpanFull(),
                                Placeholder::make('wallet.type')
                                    ->label('Type')
                                    ->content(fn (WalletAccess $record): ?string => $record->wallet?->type),
                                Placeholder::make('wallet.amount')
                                    ->label('Amount')
                                    ->content('🔒 Terenkripsi (zero-knowledge)'),
                                Placeholder::make('role')
                                    ->label('Role')
                                    ->content(fn (WalletAccess $record): ?string => $record->role),
                                Placeholder::make('is_active')
                                    ->label('Status')
                                    ->content(fn (WalletAccess $record): string => $record->is_active ? 'Active' : 'Inactive'),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?WalletAccess $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (WalletAccess $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
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
                    ->label('Wallet ID')
                    ->searchable(),
                TextColumn::make('wallet.type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('wallet.amount')
                    ->label('Amount')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => '🔒 terenkripsi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Member' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Admin' => 'Admin',
                        'Member' => 'Member',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
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
