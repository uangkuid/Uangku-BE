<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffAccountResource\Pages;
use App\Filament\Resources\StaffAccountResource\RelationManagers;
use App\Models\StaffAccount;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffAccountResource extends Resource
{
    protected static ?string $model = StaffAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'success',
                        'member' => 'warning',
                    })->sortable(),
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
            'index' => Pages\ListStaffAccounts::route('/'),
            'create' => Pages\CreateStaffAccount::route('/create'),
            'edit' => Pages\EditStaffAccount::route('/{record}/edit'),
        ];
    }
}
