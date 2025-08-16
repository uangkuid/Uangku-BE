<?php

namespace App\Filament\Resources\StaffAccounts;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\StaffAccounts\Pages\ListStaffAccounts;
use App\Filament\Resources\StaffAccounts\Pages\CreateStaffAccount;
use App\Filament\Resources\StaffAccounts\Pages\EditStaffAccount;
use App\Filament\Resources\StaffAccountResource\Pages;
use App\Filament\Resources\StaffAccountResource\RelationManagers;
use App\Models\Shop\Order;
use App\Models\StaffAccount;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class StaffAccountResource extends Resource
{
    protected static ?string $model = StaffAccount::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Staff Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Staff Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('role')
                                    ->options([
                                        'member' => 'Member',
                                        'admin' => 'Admin',
                                    ])
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Security Settings')
                            ->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                                    ->dehydrated(fn(?string $state): bool => filled($state))
                                    ->columnSpanFull(),
                                TextInput::make('password_confirmation')
                                    ->password()
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->revealable()
                                    ->maxLength(255)
                                    ->same('password')
                                    ->label('Confirm Password')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->columnSpan(['lg' => fn(?StaffAccount $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn(StaffAccount $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn(StaffAccount $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?StaffAccount $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'success',
                        'member' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->sortable(),
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
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'member' => 'Member',
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
            'index' => ListStaffAccounts::route('/'),
            'create' => CreateStaffAccount::route('/create'),
            'edit' => EditStaffAccount::route('/{record}/edit'),
        ];
    }
}
