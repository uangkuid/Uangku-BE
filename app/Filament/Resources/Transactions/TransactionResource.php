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

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('staffsus/navigation.groups.operations');
    }

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
            Section::make(__('staffsus/transactions.sections.transaction_metadata'))->schema([
                TextEntry::make('id')->label(__('staffsus/transactions.fields.transaction_id'))->copyable(),
                TextEntry::make('created_at')->label(__('staffsus/transactions.fields.time'))->dateTime(),
                TextEntry::make('transactionType.name')->label(__('staffsus/transactions.fields.type'))->badge(),
                TextEntry::make('category.name')->label(__('staffsus/transactions.fields.category'))->placeholder(__('staffsus/transactions.fields.placeholder_empty')),
                TextEntry::make('subCategory.name')->label(__('staffsus/transactions.fields.sub_category'))->placeholder(__('staffsus/transactions.fields.placeholder_empty')),
                TextEntry::make('walletTransaction.wallets')->label(__('staffsus/transactions.fields.wallet_id'))->placeholder(__('staffsus/transactions.fields.placeholder_empty'))->copyable(),
                TextEntry::make('walletTransaction.wallet.families')->label(__('staffsus/transactions.fields.family_id'))->placeholder(__('staffsus/transactions.fields.personal'))->copyable(),
                TextEntry::make('users')->label(__('staffsus/transactions.fields.user_id'))->copyable(),
                TextEntry::make('deleted_at')->label(__('staffsus/transactions.fields.deleted_at'))->dateTime()->placeholder(__('staffsus/transactions.fields.placeholder_empty')),
                TextEntry::make('amount')
                    ->label(__('staffsus/transactions.fields.amount'))
                    ->state(__('staffsus/transactions.fields.encrypted_zero_knowledge')),
                TextEntry::make('note')
                    ->label(__('staffsus/transactions.fields.note'))
                    ->state(__('staffsus/transactions.fields.encrypted_zero_knowledge'))
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
                    ->label(__('staffsus/transactions.fields.time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transactionType.name')
                    ->label(__('staffsus/transactions.fields.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('staffsus/transactions.fields.category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subCategory.name')
                    ->label(__('staffsus/transactions.fields.sub_category'))
                    ->toggleable(),
                TextColumn::make('walletTransaction.wallets')
                    ->label(__('staffsus/transactions.fields.wallet_id'))
                    ->placeholder(__('staffsus/transactions.fields.placeholder_empty'))
                    ->toggleable(),
                TextColumn::make('walletTransaction.wallet.families')
                    ->label(__('staffsus/transactions.fields.family_id'))
                    ->placeholder(__('staffsus/transactions.fields.personal'))
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label(__('staffsus/transactions.fields.amount'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn () => __('staffsus/transactions.fields.encrypted_short'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('staffsus/transactions.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? __('staffsus/transactions.statuses.deleted') : __('staffsus/transactions.statuses.active'))
                    ->color(fn (?string $state) => $state ? 'danger' : 'success')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label(__('staffsus/transactions.filters.type'))
                    ->options(fn (): array => TransactionType::query()->pluck('name', 'id')->all()),
                SelectFilter::make('categories')
                    ->label(__('staffsus/transactions.filters.category'))
                    ->options(fn (): array => Category::query()->pluck('name', 'id')->all()),
                Filter::make('wallet_id')
                    ->schema([
                        TextInput::make('value')->label(__('staffsus/transactions.filters.wallet_id')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas('walletTransaction', fn (Builder $wq) => $wq->where('wallets', $value)),
                        );
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('staffsus/transactions.filters.from')),
                        DatePicker::make('until')->label(__('staffsus/transactions.filters.until')),
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
