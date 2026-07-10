<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit log staff — READ ONLY. Log bersifat immutable; tidak ada create/edit/delete.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Security';

    protected static ?string $recordTitleAttribute = 'action';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('staffsus/navigation.groups.security');
    }

    // Log tidak boleh dibuat/diubah/dihapus dari panel.
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
            Section::make(__('staffsus/audit_logs.sections.audit_entry'))->schema([
                TextEntry::make('created_at')->label(__('staffsus/audit_logs.fields.time'))->dateTime(),
                TextEntry::make('staff.name')->label(__('staffsus/audit_logs.fields.staff'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty')),
                TextEntry::make('action')->label(__('staffsus/audit_logs.fields.action'))->badge(),
                TextEntry::make('description')->label(__('staffsus/audit_logs.fields.description'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty'))->columnSpanFull(),
                TextEntry::make('target_type')->label(__('staffsus/audit_logs.fields.target_type'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty')),
                TextEntry::make('target_id')->label(__('staffsus/audit_logs.fields.target_id'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty')),
                TextEntry::make('ip_address')->label(__('staffsus/audit_logs.fields.ip_address'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty')),
                TextEntry::make('user_agent')->label(__('staffsus/audit_logs.fields.user_agent'))->placeholder(__('staffsus/audit_logs.fields.placeholder_empty'))->columnSpanFull(),
                TextEntry::make('metadata')
                    ->label(__('staffsus/audit_logs.fields.metadata'))
                    ->placeholder(__('staffsus/audit_logs.fields.placeholder_empty'))
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('staffsus/audit_logs.fields.time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('staff.name')
                    ->label(__('staffsus/audit_logs.fields.staff'))
                    ->placeholder(__('staffsus/audit_logs.fields.placeholder_empty'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('staffsus/audit_logs.fields.action'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_type')
                    ->label(__('staffsus/audit_logs.fields.target'))
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : __('staffsus/audit_logs.fields.placeholder_empty'))
                    ->toggleable(),
                TextColumn::make('target_id')
                    ->label(__('staffsus/audit_logs.fields.target_id'))
                    ->limit(13)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label(__('staffsus/audit_logs.fields.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('staffsus/audit_logs.filters.action'))
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
                SelectFilter::make('staff_id')
                    ->label(__('staffsus/audit_logs.filters.staff'))
                    ->relationship('staff', 'name'),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('staffsus/audit_logs.filters.from')),
                        DatePicker::make('until')->label(__('staffsus/audit_logs.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
