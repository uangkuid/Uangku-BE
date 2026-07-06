<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionType;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only, metadata only (zero-knowledge): amount/note terenkripsi RSA dengan
 * public key user, server tidak bisa mendekripsi. Tidak pernah di-query/ditampilkan
 * di resource ini. Dipakai untuk telusuri pola/frekuensi & investigasi, bukan laporan keuangan.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transaction Metadata')->schema([
                TextEntry::make('id')->label('Transaction ID')->copyable(),
                TextEntry::make('created_at')->label('Waktu')->dateTime(),
                TextEntry::make('transactionType.name')->label('Tipe')->badge(),
                TextEntry::make('category.name')->label('Kategori')->placeholder('—'),
                TextEntry::make('subCategory.name')->label('Sub Kategori')->placeholder('—'),
                TextEntry::make('walletTransaction.wallets')->label('Wallet ID')->placeholder('—')->copyable(),
                TextEntry::make('walletTransaction.wallet.families')->label('Family ID')->placeholder('Personal')->copyable(),
                TextEntry::make('users')->label('User ID')->copyable(),
                TextEntry::make('deleted_at')->label('Dihapus pada')->dateTime()->placeholder('—'),
                TextEntry::make('amount')
                    ->label('Amount')
                    ->state('🔒 Terenkripsi (zero-knowledge)'),
                TextEntry::make('note')
                    ->label('Note')
                    ->state('🔒 Terenkripsi (zero-knowledge)')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['walletTransaction.wallet']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transactionType.name')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subCategory.name')
                    ->label('Sub Kategori')
                    ->toggleable(),
                TextColumn::make('walletTransaction.wallets')
                    ->label('Wallet ID')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('walletTransaction.wallet.families')
                    ->label('Family ID')
                    ->placeholder('Personal')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => '🔒 terenkripsi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? 'Deleted' : 'Active')
                    ->color(fn (?string $state) => $state ? 'danger' : 'success')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Tipe')
                    ->options(fn (): array => TransactionType::query()->pluck('name', 'id')->all()),
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->options(fn (): array => Category::query()->pluck('name', 'id')->all()),
                Filter::make('wallet_id')
                    ->schema([
                        TextInput::make('value')->label('Wallet ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas('walletTransaction', fn (Builder $wq) => $wq->where('wallets', $value)),
                        );
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }
}
