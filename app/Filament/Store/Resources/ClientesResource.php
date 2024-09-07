<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\ClientesResource\Pages;
use App\Filament\Store\Resources\ClientesResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Inputs\IdentityDocumentTextInput;

class ClientesResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Clientes';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255),


                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone_number')
                    ->label('Número de teléfono')
                    ->tel()
                    ->required()
                    ->maxLength(255),

                IdentityDocumentTextInput::make('identity_document')
                    ->required(),

                Forms\Components\DatePicker::make('birth_date')
                    ->label('Fecha de nacimiento'),

                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255),

                Forms\Components\FileUpload::make('selfie_path')
                    ->label('Selfie (Imágen)')
                    ->disk(config('filesystems.users'))
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('ci_picture_path')
                    ->label('Documento de Identidad')
                    ->disk(config('filesystems.users'))
                    ->maxFiles(1)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->hiddenOn('edit')
                    ->confirmed()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->hiddenOn('edit')
                    ->autocomplete(false)
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('new_password')
                    ->label('Nueva contraseña')
                    ->nullable()
                    ->password()
                    ->revealable()
                    ->visibleOn('edit')
                    ->maxLength(255),

                Forms\Components\TextInput::make('new_password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->visibleOn('edit')
                    ->same('new_password')
                    ->requiredWith('new_password'),

                Forms\Components\Select::make('stores')
                    ->label('Tiendas')
                    ->multiple()
                    ->options(function () {
                        return auth()->user()->stores()->pluck('stores.name', 'stores.id');
                    })
                    ->required()
                    ->preload(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search) {
                        $query
                            ->where('first_name', 'like', "{$search}%")
                            ->orWhere('last_name', 'like', "{$search}%");
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número de teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('identity_document')
                    ->label('Identidificación')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de nacimiento')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email verificado')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ? $state->format('d-m-Y H:i') : 'No verificado'),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getTableQuery()
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Obtener todos los IDs de las tiendas asociadas al usuario autenticado
        $storeIds = $authUser->stores()->pluck('stores.id')->toArray();

        // Filtrar los usuarios que están asociados a las tiendas del usuario autenticado
        return User::query()
            ->whereHas('stores', function ($query) use ($storeIds) {
                // Filtrar los usuarios que están en las tiendas del usuario autenticado
                $query->whereIn('stores.id', $storeIds);
            })
            ->role('customer'); // Filtramos directamente por el rol "customer" usando Spatie
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateClientes::route('/create'),
            'edit' => Pages\EditClientes::route('/{record}/edit'),
        ];
    }
}
