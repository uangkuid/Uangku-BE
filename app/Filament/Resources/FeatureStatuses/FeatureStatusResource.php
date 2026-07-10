<?php

namespace App\Filament\Resources\FeatureStatuses;

use App\Filament\Resources\FeatureStatuses\Pages\CreateFeatureStatus;
use App\Filament\Resources\FeatureStatuses\Pages\EditFeatureStatus;
use App\Filament\Resources\FeatureStatuses\Pages\ListFeatureStatuses;
use App\Models\FeatureStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeatureStatusResource extends Resource
{
    protected static ?string $model = FeatureStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-settings-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('staffsus/navigation.groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feature_name')
                    ->label(__('staffsus/feature_statuses.fields.feature_name'))
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
                    ->label(__('staffsus/feature_statuses.fields.is_enabled'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('staffsus/feature_statuses.fields.id'))
                    ->searchable()
                    ->hidden(),
                TextColumn::make('feature_name')
                    ->label(__('staffsus/feature_statuses.fields.feature_name'))
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->label(__('staffsus/feature_statuses.fields.is_enabled'))
                    ->boolean(),
                TextColumn::make('staffs.name')
                    ->label(__('staffsus/feature_statuses.fields.updated_by'))
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/feature_statuses.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('staffsus/feature_statuses.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_enabled')
                    ->label(__('staffsus/feature_statuses.filters.status'))
                    ->options([
                        '1' => __('staffsus/feature_statuses.filters.enabled'),
                        '0' => __('staffsus/feature_statuses.filters.disabled'),
                    ]),
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
