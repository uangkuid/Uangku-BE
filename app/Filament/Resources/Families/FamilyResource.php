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
            Section::make('Family Metadata')->schema([
                TextEntry::make('id')->label('Family ID')->copyable(),
                TextEntry::make('created_by')->label('Created By (User ID)')->copyable(),
                TextEntry::make('members_count')->label('Jumlah Member')->state(fn (Family $record) => $record->members()->count()),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->label('Last Modified')->since(),
                TextEntry::make('name')->label('Name')->state('🔒 Terenkripsi (zero-knowledge)'),
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
                    ->label('Family ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('members_count')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
