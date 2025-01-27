<?php

namespace App\Filament\Store\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class CustomerCreate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationGroup = 'Usuarios';

    protected static string $view = 'filament.pages.customer-create'; // Vista personalizada

    protected static ?string $title = 'Crear Clientes';

    public $email;

    public $first_name;

    public $last_name;

    public $phone_number;

    public $birth_date;

    public $identity_prefix; // Prefijo del documento de identidad

    public $identity_number; // Número del documento de identidad

    public $identity_document;

    public $showAdditionalFields = false; // Controla la visibilidad de los campos adicionales

    public $buttonLabel = 'Enviar Magic Link'; // Texto del botón

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->email = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->phone_number = '';
        $this->birth_date = '';
        $this->identity_document = '';
        $this->identity_prefix = '';
        $this->identity_number = '';
        $this->showAdditionalFields = false;
        $this->buttonLabel = 'Enviar Magic Link';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    $userExists = User::where('email', $state)->exists();

                    if ($userExists) {
                        $set('showAdditionalFields', false);
                        $set('buttonLabel', 'Enviar Magic Link');
                    } else {
                        $set('showAdditionalFields', true);
                        $set('buttonLabel', 'Registrar Cliente');
                    }
                }),

            Forms\Components\TextInput::make('first_name')
                ->label('Nombre')
                ->required()
                ->hidden(fn ($get) => ! $get('showAdditionalFields')),

            Forms\Components\TextInput::make('last_name')
                ->label('Apellido')
                ->required()
                ->hidden(fn ($get) => ! $get('showAdditionalFields')),

            Forms\Components\TextInput::make('phone_number')
                ->label('Número de Teléfono')
                ->required()
                ->hidden(fn ($get) => ! $get('showAdditionalFields')),

            \App\Filament\Inputs\IdentityDocumentTextInput::make()
                ->hidden(fn ($get) => ! $get('showAdditionalFields')),

            Forms\Components\DatePicker::make('birth_date')
                ->label('Fecha de Nacimiento')
                ->nullable()
                ->hidden(fn ($get) => ! $get('showAdditionalFields')),

        ];
    }

    public function submit()
    {
        // Obtener la tienda actual usando Filament::getTenant()
        $currentStore = Filament::getTenant();

        if (! $currentStore) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo identificar la tienda actual.')
                ->danger()
                ->send();

            return;
        }

        $currentStoreId = $currentStore->id; // ID de la tienda actual

        // Buscar el usuario por correo electrónico
        $user = User::where('email', $this->email)->first();

        if ($user) {
            // Validar si el usuario en sesión coincide
            if ($user->id === auth()->id()) {
                Notification::make()
                    ->title('Error')
                    ->body('No puedes registrar al usuario en sesión como cliente.')
                    ->danger()
                    ->send();

                return;
            }

            // Verificar si el usuario ya está asociado a la tienda como `customer`
            $existingCustomerRole = $user->stores()
                ->wherePivot('store_id', $currentStoreId)
                ->wherePivot('role', 'customer')
                ->exists();

            if ($existingCustomerRole) {
                Notification::make()
                    ->title('Cliente existente')
                    ->body('El usuario ya está asociado como cliente a esta tienda.')
                    ->warning()
                    ->send();

                return;
            }

            // Asociar al usuario como cliente (nueva entrada con rol `customer`)
            $user->assignRole('customer');
            $user->stores()->attach($currentStoreId, ['role' => 'customer']);
            $this->sendMagicLink($user);

            Notification::make()
                ->title('Cliente registrado')
                ->body('El usuario fue asociado como cliente a la tienda y se le envió un enlace mágico.')
                ->success()
                ->send();
        } else {

            try {
                $this->identity_document = $this->identity_prefix.'-'.$this->identity_number;

                if (User::where('email', $this->email)->exists()) {
                    /* Notificación */
                    Notification::make()
                        ->title('Error crítico')
                        ->body('El email ya esta registrado.')
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'email' => 'El correo electrónico ya está registrado.',
                    ]);

                }
                if (! empty($this->phone_number) && User::where('phone_number', $this->phone_number)->exists()) {
                    /* Notificación */
                    Notification::make()
                        ->title('Error crítico')
                        ->body('El telefono ya se encuentra asociado a otro usuario')
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'email' => 'El correo electrónico ya está registrado.',
                    ]);

                }
                if (User::where('identity_document', $this->identity_document)->exists()) {
                    /* Notificación */
                    Notification::make()
                        ->title('Error crítico')
                        ->body('El documento de identidad ya se encuentra asociado a otro usuario')
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'email' => 'El correo electrónico ya está registrado.',
                    ]);
                }

                // Crear un nuevo cliente
                $newUser = User::create([
                    'email' => $this->email,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'phone_number' => $this->phone_number,
                    'birth_date' => $this->birth_date ?: null,
                    'password' => bcrypt('default_password'),
                    'identity_document' => $this->identity_document,
                ]);

                $newUser->assignRole('customer');
                $newUser->stores()->attach($currentStoreId, ['role' => 'customer']);

                Notification::make()
                    ->title('Cliente registrado')
                    ->body('El cliente fue registrado exitosamente y se le envió un enlace mágico.')
                    ->success()
                    ->send();

                $this->resetForm();

                $this->sendMagicLink($newUser);

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error crítico')
                    ->body('Ocurrió un error inesperado: '.$e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    protected function sendMagicLink(User $user): void
    {
        $action = new LoginAction($user);
        $magicLinkUrl = MagicLink::create($action)->url;

        $store = Filament::getTenant(); // Obtener la tienda actual
        $storeName = $store ? $store->name : 'Nuestra Tienda'; // Nombre de la tienda o valor por defecto

        $user->notify(new \App\Notifications\WelcomeCustomerNotification($magicLinkUrl, $storeName));
    }
}
