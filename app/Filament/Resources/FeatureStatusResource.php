<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureStatusResource\Pages;
use App\Filament\Resources\FeatureStatusResource\RelationManagers;
use App\Models\FeatureStatus;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'tabler-settings-check';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('feature_name')
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('staffs.name')
                    ->label('Updated By')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
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
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFeatureStatuses::route('/'),
            'create' => Pages\CreateFeatureStatus::route('/create'),
            'edit' => Pages\EditFeatureStatus::route('/{record}/edit'),
        ];
    }
}
