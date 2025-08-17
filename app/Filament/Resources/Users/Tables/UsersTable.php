<?php

namespace App\Filament\Resources\Users\Tables;

use App\Helpers\EncryptionHelper;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('avatar')
                    ->disk('minio') // Menentukan disk yang digunakan
                    ->visibility('private') // Mengatur visibilitas gambar menjadi privat
                    ->getStateUsing(function ($record) {
                        if ($record->avatar) {
                            return Storage::disk('minio')->temporaryUrl(
                                "avatar/{$record->id}/{$record->avatar}",
                                now()->addMinutes(60)
                            );
                        }
                        return null;
                    })
                    ->imageSize(40)
                    ->circular()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
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
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
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
                //
            ])
            ->recordActions([
//                EditAction::make(),
            ])
            ->toolbarActions([
//                BulkActionGroup::make([
//                    DeleteBulkAction::make(),
//                ]),
            ]);
    }
}
