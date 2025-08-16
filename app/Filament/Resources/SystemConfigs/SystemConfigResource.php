<?php

namespace App\Filament\Resources\SystemConfigs;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SystemConfigs\Pages\ListSystemConfigs;
use App\Filament\Resources\SystemConfigs\Pages\CreateSystemConfig;
use App\Filament\Resources\SystemConfigs\Pages\EditSystemConfig;
use App\Filament\Resources\SystemConfigResource\Pages;
use App\Filament\Resources\SystemConfigResource\RelationManagers;
use App\Models\SystemConfig;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    protected static string | \BackedEnum | null $navigationIcon = 'tabler-settings-cog';
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('key')
                    ->searchable(),
                TextColumn::make('value')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => ListSystemConfigs::route('/'),
            'create' => CreateSystemConfig::route('/create'),
            'edit' => EditSystemConfig::route('/{record}/edit'),
        ];
    }

//    public static function mutateFormDataBeforeCreate(array $data): array
//    {
//        $data['updated_by'] = auth()->id(); // atau auth()->id()
//        return $data;
//    }
}
