<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BankAccountResource\Pages;
use App\Filament\App\Resources\BankAccountResource\RelationManagers;
use App\Models\BankAccount;
use App\Enums\BankEnum;
use App\Enums\PhonePrefixEnum;
use App\Enums\IdentityPrefixEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Cuentas en Bs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bank_code')
                    ->label('Banco')
                    ->options(
                        collect(BankEnum::cases())->mapWithKeys(fn($bank) => [$bank->code() => $bank->getLabel()])->toArray()
                    )
                    ->required(),

                Grid::make(2)
                    ->schema([
                        Select::make('phone_prefix')
                            ->label('Prefijo Telefónico')
                            ->options(
                                collect(PhonePrefixEnum::cases())
                                    ->mapWithKeys(fn($prefix) => [$prefix->value => $prefix->getLabel()])
                                    ->toArray()
                            )
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Número Telefónico')
                            ->numeric()
                            ->minLength(7)
                            ->maxLength(7)
                            ->required(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('bank_code')
                    ->label('Banco')
                    ->formatStateUsing(function ($state) {
                        // Buscar el enum correspondiente al código del banco
                        $bank = collect(BankEnum::cases())
                            ->first(fn($bank) => $bank->code() === $state);

                        return $bank?->getLabel() ?? 'Desconocido';
                    }),
                TextColumn::make('phone_number')
                    ->label('Número de teléfono'),
                TextColumn::make('identity_number')
                    ->label('Número de identidad'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date(),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
