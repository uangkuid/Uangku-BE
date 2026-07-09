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
            Section::make('Wallet Metadata')->schema([
                TextEntry::make('id')->label('Wallet ID')->copyable(),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'danger'),
                TextEntry::make('families')->label('Family ID')->placeholder('Personal')->copyable(),
                TextEntry::make('created_by')->label('Created By (User ID)')->copyable(),
                TextEntry::make('accesses_count')->label('Jumlah Member')->state(fn (Wallet $record) => $record->accesses()->count()),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->label('Last Modified')->since(),
                TextEntry::make('name')->label('Name')->state('🔒 Terenkripsi (zero-knowledge)'),
                TextEntry::make('amount')->label('Amount')->state('🔒 Terenkripsi (zero-knowledge)'),
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
                    ->label('Wallet ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('accesses_count')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('families')
                    ->label('Family ID')
                    ->placeholder('Personal')
                    ->toggleable(),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'personal' => 'Personal',
                        'family' => 'Family',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('freeze')
                    ->label('Freeze')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (Wallet $record) => self::staffCan('Wallet:Freeze') && $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalDescription('Wallet dibekukan sementara (bukan aksi finansial). Investigasi penyalahgunaan.')
                    ->action(function (Wallet $record) {
                        $record->update(['status' => 'inactive']);
                        AuditLog::record('wallet.freeze', $record, [], 'Freeze wallet');
                        Notification::make()->title('Wallet dibekukan')->success()->send();
                    }),
                Action::make('unfreeze')
                    ->label('Unfreeze')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (Wallet $record) => self::staffCan('Wallet:Freeze') && $record->status === 'inactive')
                    ->requiresConfirmation()
                    ->action(function (Wallet $record) {
                        $record->update(['status' => 'active']);
                        AuditLog::record('wallet.unfreeze', $record, [], 'Unfreeze wallet');
                        Notification::make()->title('Wallet diaktifkan kembali')->success()->send();
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
