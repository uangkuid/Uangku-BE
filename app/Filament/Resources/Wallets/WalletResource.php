<?php

namespace App\Filament\Resources\Wallets;

use App\Filament\Resources\Wallets\Pages\ListWallets;
use App\Filament\Resources\Wallets\Pages\ViewWallet;
use App\Models\AuditLog;
use App\Models\Wallet;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only, metadata only (zero-knowledge): nama wallet & saldo terenkripsi RSA,
 * server tidak bisa mendekripsi. Satu-satunya aksi adalah freeze/unfreeze (non-finansial)
 * untuk kasus penyalahgunaan.
 */
class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

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

    private static function staffCan(string $permission): bool
    {
        return (bool) Filament::auth()->user()?->can($permission);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('staffsus/wallets.sections.wallet_metadata'))->schema([
                TextEntry::make('id')->label(__('staffsus/wallets.fields.wallet_id'))->copyable(),
                TextEntry::make('type')->label(__('staffsus/wallets.fields.type'))->badge(),
                TextEntry::make('status')->label(__('staffsus/wallets.fields.status'))->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'danger'),
                TextEntry::make('families')->label(__('staffsus/wallets.fields.family_id'))->placeholder(__('staffsus/wallets.fields.personal'))->copyable(),
                TextEntry::make('created_by')->label(__('staffsus/wallets.fields.created_by_user_id'))->copyable(),
                TextEntry::make('accesses_count')->label(__('staffsus/wallets.fields.member_count'))->state(fn (Wallet $record) => $record->accesses()->count()),
                TextEntry::make('created_at')->label(__('staffsus/wallets.fields.created_at'))->dateTime(),
                TextEntry::make('updated_at')->label(__('staffsus/wallets.fields.updated_at'))->since(),
                TextEntry::make('name')->label(__('staffsus/wallets.fields.name'))->state(__('staffsus/wallets.fields.encrypted_zero_knowledge')),
                TextEntry::make('amount')->label(__('staffsus/wallets.fields.amount'))->state(__('staffsus/wallets.fields.encrypted_zero_knowledge')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('accesses'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('staffsus/wallets.fields.wallet_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('staffsus/wallets.fields.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('staffsus/wallets.fields.status'))
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('accesses_count')
                    ->label(__('staffsus/wallets.fields.members'))
                    ->sortable(),
                TextColumn::make('families')
                    ->label(__('staffsus/wallets.fields.family_id'))
                    ->placeholder(__('staffsus/wallets.fields.personal'))
                    ->toggleable(),
                TextColumn::make('created_by')
                    ->label(__('staffsus/wallets.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('staffsus/wallets.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('staffsus/wallets.fields.type'))
                    ->options([
                        'personal' => __('staffsus/wallets.filters.type_personal'),
                        'family' => __('staffsus/wallets.filters.type_family'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('staffsus/wallets.fields.status'))
                    ->options([
                        'active' => __('staffsus/wallets.filters.status_active'),
                        'inactive' => __('staffsus/wallets.filters.status_inactive'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('freeze')
                    ->label(__('staffsus/wallets.actions.freeze'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (Wallet $record) => self::staffCan('Wallet:Freeze') && $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalDescription(__('staffsus/wallets.modals.freeze_description'))
                    ->action(function (Wallet $record) {
                        $record->update(['status' => 'inactive']);
                        AuditLog::record('wallet.freeze', $record, [], 'Freeze wallet');
                        Notification::make()->title(__('staffsus/wallets.notifications.frozen'))->success()->send();
                    }),
                Action::make('unfreeze')
                    ->label(__('staffsus/wallets.actions.unfreeze'))
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (Wallet $record) => self::staffCan('Wallet:Freeze') && $record->status === 'inactive')
                    ->requiresConfirmation()
                    ->action(function (Wallet $record) {
                        $record->update(['status' => 'active']);
                        AuditLog::record('wallet.unfreeze', $record, [], 'Unfreeze wallet');
                        Notification::make()->title(__('staffsus/wallets.notifications.unfrozen'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
            'view' => ViewWallet::route('/{record}'),
        ];
    }
}
