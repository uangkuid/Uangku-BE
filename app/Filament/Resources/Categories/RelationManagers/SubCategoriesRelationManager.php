<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Helpers\EncryptionHelper;
use App\Models\SubCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'subCategories';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === 'App\Filament\Resources\Categories\Pages\ViewCategory';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('staffsus/categories.relation_managers.sub_categories');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        Section::make(__('staffsus/categories.sub_categories.sections.sub_category_information'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('staffsus/categories.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                        Section::make(__('staffsus/categories.sub_categories.sections.users_information'))
                            ->schema([
                                Placeholder::make('user.id')
                                    ->label(__('staffsus/categories.sub_categories.fields.user_id'))
                                    ->content(fn (SubCategory $record): ?string => $record->user?->id),
                                Placeholder::make('user.email')
                                    ->label(__('staffsus/categories.sub_categories.fields.email'))
                                    ->content(fn (SubCategory $record): ?string => $record->user?->email
                                        ? EncryptionHelper::decryptEmail($record->user->email)
                                        : null)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?SubCategory $record) => $record === null ? 3 : 2]),
                Section::make(__('staffsus/categories.sub_categories.sections.general_information'))
                    ->schema([
                        Placeholder::make('created_at')
                            ->label(__('staffsus/categories.sub_categories.fields.created_at'))
                            ->content(fn (SubCategory $record): ?string => $record->created_at?->timezone('Asia/Jakarta')->format('d M Y - H:i:s')),

                        Placeholder::make('updated_at')
                            ->label(__('staffsus/categories.sub_categories.fields.updated_at'))
                            ->content(fn (SubCategory $record): ?string => $record->updated_at?->timezone('Asia/Jakarta')->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?SubCategory $record) => $record === null),
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
                    ->label(__('staffsus/categories.sub_categories.fields.user_email'))
                    ->formatStateUsing(fn ($state) => $state ? EncryptionHelper::decryptEmail($state) : null)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Cek apakah input merupakan email valid
                        if (! filter_var($search, FILTER_VALIDATE_EMAIL)) {
                            return $query; // Skip filter, biar nggak error dan tetap bisa search lainnya
                        }

                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('blind_index', EncryptionHelper::blindIndex($search));
                        });
                    })
                    ->toggleable(),
                TextColumn::make('users')
                    ->label(__('staffsus/categories.sub_categories.fields.users'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('families')
                    ->label(__('staffsus/categories.sub_categories.fields.families'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('staffsus/categories.sub_categories.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('staffsus/categories.sub_categories.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('staffsus/categories.sub_categories.filters.type'))
                    ->options([
                        'personal' => __('staffsus/categories.sub_categories.filters.personal_only'),
                        'family' => __('staffsus/categories.sub_categories.filters.family_only'),
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all') {
                            'personal' => $query->whereNull('families'),
                            'family' => $query->whereNotNull('families'),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('user.email')
                    ->getTitleFromRecordUsing(fn (SubCategory $record): string => EncryptionHelper::decryptEmail($record->user->email))
                    ->collapsible(),
                Group::make('families')
                    ->label(__('staffsus/categories.sub_categories.groups.family'))
                    ->getTitleFromRecordUsing(function (SubCategory $record): string {
                        return $record->families ?? __('staffsus/categories.sub_categories.filters.personal_only');
                    })
                    ->collapsible(),
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
