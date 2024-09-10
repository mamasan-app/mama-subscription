<?php

declare(strict_types=1);

namespace App\Filament\Store\Pages;

use App\Filament\Store\Fields\StoreFileUpload;
use App\Models\Address;  // Importamos el modelo Address
use App\Models\Store;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegisterStore extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Registrar Tienda';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                StoreFileUpload::make('logo')
                    ->avatar(),

                TextInput::make('name')
                    ->label('Nombre de tu tienda')
                    ->required(),

                TextInput::make('slug')
                    ->required()
                    ->label('Link')
                    ->minLength(3)
                    ->maxLength(20)
                    ->unique(Store::class, 'slug') // Asegúrate de que sea único en la tabla Store
                    ->prefix('https://')
                    ->suffix('.' . config('app.domain'))
                    ->placeholder('mitienda')
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),

                // Aquí guardaremos la dirección corta (short_address)
                TextInput::make('short_address')
                    ->label('Dirección Corta de la tienda')
                    ->required(),

                // Aquí guardaremos la dirección larga (long_address)
                TextInput::make('long_address')
                    ->label('Dirección Completa de la tienda')
                    ->required(),
            ]);
    }

    /**
     * @throws Throwable
     */
    protected function handleRegistration(array $data): Store
    {
        return DB::transaction(function () use ($data) {
            /** @var User $authUser */
            $authUser = auth()->user();

            // Creamos la tienda sin el campo "address"
            $storeData = array_merge($data, ['owner_id' => $authUser->id]);
            $store = Store::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'owner_id' => $authUser->id,
                'logo' => $data['logo'] ?? null,  // Si tienes un campo para el logo
            ]);

            // Asociar el usuario autenticado a la tienda como 'owner_store'
            $store->users()->attach($authUser->id, ['role' => 'owner_store']);

            // Creamos la dirección relacionada con la tienda
            Address::create([
                'short_address' => $data['short_address'],
                'long_address' => $data['long_address'],
                'store_id' => $store->id,  // Asociamos la dirección a la tienda
            ]);

            return $store;
        });
    }
}
