<?php

namespace App\Filament\Resources\FeatureStatuses;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\FeatureStatuses\Pages\ListFeatureStatuses;
use App\Filament\Resources\FeatureStatuses\Pages\CreateFeatureStatus;
use App\Filament\Resources\FeatureStatuses\Pages\EditFeatureStatus;
use App\Filament\Resources\FeatureStatusResource\Pages;
use App\Filament\Resources\FeatureStatusResource\RelationManagers;
use App\Models\FeatureStatus;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeatureStatusResource extends Resource
{
    protected static ?string $model = FeatureStatus::class;

    protected static string | \BackedEnum | null $navigationIcon = 'tabler-settings-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feature_name')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(
                        table: 'feature_statuses',
                        column: 'feature_name',
                        ignoreRecord: true
                    )
                    ->columnSpanFull(),
                Toggle::make('is_enabled')
                    ->label('Enabled')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('feature_name')
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('staffs.name')
                    ->label('Updated By')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_enabled')
                    ->label('Status')
                    ->options([
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ])
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatureStatuses::route('/'),
            'create' => CreateFeatureStatus::route('/create'),
            'edit' => EditFeatureStatus::route('/{record}/edit'),
        ];
    }
}
