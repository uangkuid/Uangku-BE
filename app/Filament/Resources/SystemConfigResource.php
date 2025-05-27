<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemConfigResource\Pages;
use App\Filament\Resources\SystemConfigResource\RelationManagers;
use App\Models\SystemConfig;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SystemConfigResource extends Resource
{
    protected static ?string $model = SystemConfig::class;

    protected static ?string $navigationIcon = 'tabler-settings-cog';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(
                        table: 'system_configs',
                        column: 'key',
                        ignoreRecord: true
                    )
                    ->columnSpanFull(),
                Textarea::make('value')
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
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListSystemConfigs::route('/'),
            'create' => Pages\CreateSystemConfig::route('/create'),
            'edit' => Pages\EditSystemConfig::route('/{record}/edit'),
        ];
    }

//    public static function mutateFormDataBeforeCreate(array $data): array
//    {
//        $data['updated_by'] = auth()->id(); // atau auth()->id()
//        return $data;
//    }
}
