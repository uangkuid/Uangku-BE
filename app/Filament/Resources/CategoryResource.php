<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'tabler-category';
    protected static ?string $navigationGroup = 'Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Category Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('transaction_types')
                                    ->required()
                                    ->relationship('transactionTypes', 'name')
                            ]),
                        Forms\Components\Section::make('Image')
                            ->schema([
                                FileUpload::make('icon')
                                    ->hiddenLabel()
                                    ->disk('minio')
                                    ->directory('category')
                                    ->visibility('private')
                                    ->image()
                                    ->imagePreviewHeight('100') // optional preview
                                    ->previewable(true), // pastikan preview aktif
                            ]),
                    ])
                    ->columnSpan(['lg' => fn(?Category $record) => $record === null ? 3 : 2]),
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn(Category $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn(Category $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Category $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->hidden()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('icon')
                    ->disk('minio') // Menentukan disk yang digunakan
                    ->visibility('private') // Mengatur visibilitas gambar menjadi privat
                    ->getStateUsing(function ($record) {
                        if ($record->icon) {
                            return Storage::disk('minio')->temporaryUrl(
                                "category/{$record->icon}",
                                now()->addMinutes(60)
                            );
                        }
                        return null;
                    })
                    ->height(32),
                TextColumn::make('transactionTypes.name')
                    ->label("Transaction Type")
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('transactionTypes', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Transaction Type')
                    ->relationship('transactionTypes', 'name')
                    ->options([
                        'Spending' => 'Spending',
                        'Income' => 'Income',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('id')
                                            ->label('Category ID')
                                            ->copyable()
                                            ->copyMessage('Copied!')
                                            ->copyMessageDuration(1500),
                                        TextEntry::make('name'),
                                        TextEntry::make('transactionTypes.name')
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'Income' => 'success',
                                                'Spending', 'Expense', 'Expanse' => 'danger', // Tambahkan variasi jika perlu
                                                default => 'primary',
                                            })
                                    ]),
                                    Group::make([
                                        TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->label('Last Modified At')
                                            ->since(),
                                    ]),
                                ]),
                            ImageEntry::make('icon')
                                ->disk('minio')
                                ->visibility('private')
                                ->hiddenLabel()
                                ->getStateUsing(function ($record) {
                                    return Storage::disk('minio')->temporaryUrl(
                                        "category/{$record->icon}",
                                        now()->addMinutes(60)
                                    );
                                })
                                ->grow(false),
                        ])->from('lg'),
                    ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubCategoriesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
            'view' => Pages\ViewCategory::route('/{record}'),
        ];
    }
}
