<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Helpers\EncryptionHelper;
use App\Models\SubCategory;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'subCategories';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === 'App\Filament\Resources\CategoryResource\Pages\ViewCategory';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Group::make()
                    ->schema([
                        Section::make('Sub Category Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Users Information')
                            ->schema([
                                Placeholder::make('user.id')
                                    ->label('User ID')
                                    ->content(fn(SubCategory $record): ?string => $record->user?->id),
                                Placeholder::make('user.email')
                                    ->label('Email')
                                    ->content(fn(SubCategory $record): ?string => EncryptionHelper::decryptFromString(
                                        encryptedData: $record->user?->email,
                                        key: EncryptionHelper::getSystemSecretKey()
                                    ))
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->columnSpan(['lg' => fn(?SubCategory $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn(SubCategory $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn(SubCategory $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?SubCategory $record) => $record === null),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('id')
                    ->hidden()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label("User Email")
                    ->formatStateUsing(fn($state) => EncryptionHelper::decryptFromString($state, EncryptionHelper::getSystemSecretKey()))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Cek apakah input merupakan email valid
                        if (!filter_var($search, FILTER_VALIDATE_EMAIL)) {
                            return $query; // Skip filter, biar nggak error dan tetap bisa search lainnya
                        }
                        return $query->whereHas('user', function ($q) use ($search) {
                            $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
                            $q->where('email', EncryptionHelper::encryptAsString(
                                data: $search,
                                key: EncryptionHelper::getSystemSecretKey(),
                                iv: $staticIv,
                            ));
                        });
                    })
                    ->toggleable(),
                TextColumn::make('users')
                    ->label("User ID")
                    ->searchable()
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
            ->filters([

            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('user.email')
                    ->getTitleFromRecordUsing(fn(SubCategory $record): string => EncryptionHelper::decryptFromString($record->user->email, EncryptionHelper::getSystemSecretKey()))
                    ->collapsible()
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

//    public static function canViewForRecord(Model $ownerRecord): bool
//    {
//        // Tampilkan hanya jika sedang view (bukan edit)
//        return request()->routeIs('filament.admin.resources.categories.view');
//    }
}
