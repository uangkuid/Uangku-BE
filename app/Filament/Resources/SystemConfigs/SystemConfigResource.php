<?php

namespace App\Filament\Resources\SystemConfigs;

use App\Filament\Resources\SystemConfigs\Pages\CreateSystemConfig;
use App\Filament\Resources\SystemConfigs\Pages\EditSystemConfig;
use App\Filament\Resources\SystemConfigs\Pages\ListSystemConfigs;
use App\Models\SystemConfig;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SystemConfigResource extends Resource
{
    protected static ?string $model = SystemConfig::class;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-settings-cog';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('staffsus/navigation.groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label(__('staffsus/system_configs.fields.key'))
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
                    ->label(__('staffsus/system_configs.fields.value'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('staffsus/system_configs.fields.id'))
                    ->searchable()
                    ->hidden(),
                TextColumn::make('key')
                    ->label(__('staffsus/system_configs.fields.key'))
                    ->searchable(),
                TextColumn::make('value')
                    ->label(__('staffsus/system_configs.fields.value'))
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/system_configs.fields.updated_at'))
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
