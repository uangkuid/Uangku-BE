<?php

namespace App\Filament\Resources\Families;

use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Resources\Families\Pages\ViewFamily;
use App\Filament\Resources\Families\RelationManagers\MembersRelationManager;
use App\Models\Family;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only, metadata only (zero-knowledge): nama family terenkripsi RSA,
 * server tidak bisa mendekripsi. Untuk investigasi masalah sharing keluarga.
 */
class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

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
            Section::make(__('staffsus/families.sections.family_metadata'))->schema([
                TextEntry::make('id')->label(__('staffsus/families.fields.family_id'))->copyable(),
                TextEntry::make('created_by')->label(__('staffsus/families.fields.created_by_user_id'))->copyable(),
                TextEntry::make('members_count')->label(__('staffsus/families.fields.member_count'))->state(fn (Family $record) => $record->members()->count()),
                TextEntry::make('created_at')->label(__('staffsus/families.fields.created_at'))->dateTime(),
                TextEntry::make('updated_at')->label(__('staffsus/families.fields.updated_at'))->since(),
                TextEntry::make('name')->label(__('staffsus/families.fields.name'))->state(__('staffsus/families.fields.encrypted_zero_knowledge')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('members'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('staffsus/families.fields.family_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('members_count')
                    ->label(__('staffsus/families.fields.members'))
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label(__('staffsus/families.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('staffsus/families.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/families.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
            'view' => ViewFamily::route('/{record}'),
        ];
    }
}
