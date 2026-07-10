<?php

namespace App\Filament\Resources\Families\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user')
            ->columns([
                TextColumn::make('user')
                    ->label(__('staffsus/families.members.fields.user_id'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('staffsus/families.members.fields.role'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Owner' => 'danger',
                        'Admin' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label(__('staffsus/families.members.fields.status'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Active' => 'success',
                        'Revoked' => 'danger',
                        'Left' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('staffsus/families.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('staffsus/families.members.fields.role'))
                    ->options([
                        'Owner' => __('staffsus/families.members.roles.owner'),
                        'Admin' => __('staffsus/families.members.roles.admin'),
                        'Member' => __('staffsus/families.members.roles.member'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('staffsus/families.members.fields.status'))
                    ->options([
                        'Active' => __('staffsus/families.members.statuses.active'),
                        'Revoked' => __('staffsus/families.members.statuses.revoked'),
                        'Left' => __('staffsus/families.members.statuses.left'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
