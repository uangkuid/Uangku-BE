<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Categories\RelationManagers\SubCategoriesRelationManager;
use App\Helpers\StorageHelper;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-category';

    protected static string|\UnitEnum|null $navigationGroup = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Category Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('transaction_types')
                                    ->required()
                                    ->relationship('transactionTypes', 'name'),
                            ]),
                        Section::make('Image')
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
                    ->columnSpan(['lg' => fn (?Category $record) => $record === null ? 3 : 2]),
                Section::make('General Information')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (Category $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (Category $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Category $record) => $record === null),
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
                            return StorageHelper::temporaryUrl(
                                'minio',
                                "category/{$record->icon}",
                                now()->addMinutes(60)
                            );
                        }

                        return null;
                    })
                    ->height(32),
                TextColumn::make('transactionTypes.name')
                    ->label('Transaction Type')
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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Flex::make([
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
                                            ->color(fn ($state) => match ($state) {
                                                'Income' => 'success',
                                                'Spending', 'Expense', 'Expanse' => 'danger', // Tambahkan variasi jika perlu
                                                default => 'primary',
                                            }),
                                    ]),
                                    Group::make([
                                        TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->label('Last Modified At')
                                            ->since(),
                                    ]),
                                ])
                                ->grow(true),
                            ImageEntry::make('icon')
                                ->disk('minio')
                                ->visibility('private')
                                ->hiddenLabel()
                                ->getStateUsing(function ($record) {
                                    if (! $record->icon) {
                                        return null;
                                    }

                                    return StorageHelper::temporaryUrl(
                                        'minio',
                                        "category/{$record->icon}",
                                        now()->addMinutes(60)
                                    );
                                })
                                ->grow(false),
                        ])->from('lg'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SubCategoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }
}
