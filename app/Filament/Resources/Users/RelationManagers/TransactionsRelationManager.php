<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Transaction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Zero-knowledge: amount/note/nama wallet terenkripsi RSA dengan public key user,
 * server tidak bisa mendekripsi. Hanya metadata plaintext yang ditampilkan.
 */
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

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
                        Section::make('Transaction Information')
                            ->schema([
                                Placeholder::make('amount')
                                    ->label('Amount')
                                    ->content('🔒 Terenkripsi (zero-knowledge)'),
                                Placeholder::make('note')
                                    ->label('Note')
                                    ->content('🔒 Terenkripsi (zero-knowledge)')
                                    ->columnSpanFull(),
                                Placeholder::make('category.name')
                                    ->label('Category')
                                    ->content(fn (Transaction $record): ?string => $record->category?->name),
                                Placeholder::make('subCategory.name')
                                    ->label('Sub Category')
                                    ->content(fn (Transaction $record): ?string => $record->subCategory?->name),
                                Placeholder::make('wallet_id')
                                    ->label('Wallet ID')
                                    ->content(fn (Transaction $record): ?string => $record->walletTransaction?->wallets),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?Transaction $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (Transaction $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (Transaction $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),

                        Placeholder::make('deleted_at')
                            ->label('Deleted at')
                            ->content(fn (Transaction $record): ?string => $record->deleted_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s'))
                            ->visible(fn (Transaction $record): bool => $record->deleted_at !== null),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Transaction $record) => $record === null),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label('Amount')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => '🔒 terenkripsi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->label('Note')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => '🔒 terenkripsi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subCategory.name')
                    ->label('Sub Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('walletTransaction.wallets')
                    ->label('Wallet ID')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // No create/edit actions - read only
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
