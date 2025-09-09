<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\ViewAction;
use App\Models\WalletAccess;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        Section::make('Wallet Information')
                            ->schema([
                                Placeholder::make('wallet.name')
                                    ->label('Wallet Name')
                                    ->content(fn(WalletAccess $record): ?string => $record->wallet?->name)
                                    ->columnSpanFull(),
                                Placeholder::make('wallet.amount')
                                    ->label('Amount')
                                    ->content(fn(WalletAccess $record): ?string => number_format((float)($record->wallet?->amount ?? 0), 0, ',', '.')),
                                Placeholder::make('role')
                                    ->label('Role')
                                    ->content(fn(WalletAccess $record): ?string => $record->role),
                                Placeholder::make('is_active')
                                    ->label('Status')
                                    ->content(fn(WalletAccess $record): string => $record->is_active ? 'Active' : 'Inactive'),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn(?WalletAccess $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn(WalletAccess $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn(WalletAccess $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?WalletAccess $record) => $record === null),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('wallet.name')
            ->columns([
                TextColumn::make('wallet.name')
                    ->label('Wallet Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('wallet.amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state) => number_format((float)($state ?? 0), 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Member' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn(bool $state): string => $state ? 'success' : 'danger'),
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
