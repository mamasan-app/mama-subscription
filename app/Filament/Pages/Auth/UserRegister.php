<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Inputs\IdentityDocumentTextInput;
use App\Models\Store;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as FilamentRegister;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class UserRegister extends FilamentRegister
{
    protected static string $view = 'filament.pages.auth.register';

    protected static string $layout = 'filament.components.layout.register';

    protected ?string $maxWidth = MaxWidth::FourExtraLarge->value;

    public function mount(): void
    {
        parent::mount();

        if (config('app.env') !== 'local') {
            return;
        }

        $this->form->fill(array_merge(
            User::factory()->make()->toArray(),
            [
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        ));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // Paso 1: Información General del Usuario
                    Wizard\Step::make('Información General')
                        ->columns(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->label('Nombre')
                                ->placeholder('Carlos'),

                            TextInput::make('last_name')
                                ->required()
                                ->label('Apellido')
                                ->placeholder('Pérez'),

                            TextInput::make('email')
                                ->required()
                                ->email()
                                ->label('Correo Electrónico')
                                ->placeholder('carlosperez@example.com'),

                            TextInput::make('phone_number')
                                ->required()
                                ->label('Número de Teléfono')
                                ->placeholder('04241234567'),

                            TextInput::make('password')
                                ->required()
                                ->password()
                                ->label('Contraseña')
                                ->placeholder('********')
                                ->rule(Password::default())
                                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                ->same('password_confirmation')
                                ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),

                            TextInput::make('password_confirmation')
                                ->required()
                                ->password()
                                ->label('Confirmar Contraseña')
                                ->placeholder('********')
                                ->dehydrated(false),
                        ]),

                    // Paso 2: Información Personal del Usuario
                    Wizard\Step::make('Información Personal')
                        ->columns(2)
                        ->schema([
                            IdentityDocumentTextInput::make()
                                ->required(),

                            DatePicker::make('birth_date')
                                ->label('Fecha de Nacimiento')
                                ->placeholder('01/01/2000'),

                            Textarea::make('address')
                                ->required()
                                ->columnSpanFull()
                                ->label('Dirección')
                                ->placeholder('Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1'),

                            FileUpload::make('selfie_path')
                                ->label('Selfie')
                                ->image()
                                ->disk(config('filesystems.users'))
                                ->maxFiles(1)
                                ->placeholder('selfie.jpg'),

                            FileUpload::make('ci_picture_path')
                                ->label('Foto de la Cédula')
                                ->image()
                                ->disk(config('filesystems.users'))
                                ->maxFiles(1)
                                ->placeholder('ci.jpg'),

                            Checkbox::make('terms_and_conditions_accepted')
                                ->columnSpanFull()
                                ->accepted()
                                ->label('Acepto los términos y condiciones'),
                        ]),

                    // Paso 3: Información de la Tienda
                    Wizard\Step::make('Información de la Tienda')
                        ->columns(2)
                        ->schema([
                            TextInput::make('store_name')
                                ->label('Nombre de la Tienda')
                                ->required(),

                            Textarea::make('store_description')
                                ->label('Descripción de la Tienda')
                                ->required()
                                ->placeholder('Descripción breve de la tienda'),
                            Textarea::make('address_store')
                                ->label('Direccion del Negocio')
                                ->required()
                                ->placeholder('Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1'),

                            FileUpload::make('store_rif_path')
                                ->label('RIF de la Tienda')
                                ->disk(config('filesystems.stores'))
                                ->maxFiles(1)
                                ->placeholder('rif.jpg'),

                            FileUpload::make('certificate_of_incorporation_path')
                                ->label('Certificado de Incorporación')
                                ->disk(config('filesystems.stores'))
                                ->maxFiles(1)
                                ->placeholder('certificate.jpg'),
                        ]),
                ])->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        Registrarse
                    </x-filament::button>
                BLADE))),
            ]);
    }

    public function register(): RegistrationResponse|null
    {
        $data = $this->form->getState();

        // Crear el usuario
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => $data['password'],
            'identity_document' => $data['identity_document'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'selfie_path' => $data['selfie_path'],
            'ci_picture_path' => $data['ci_picture_path'],
        ]);

        // Asignar el rol de "owner_store" al usuario
        $user->assignRole('owner_store');

        // Crear la tienda y asociarla al usuario
        $store = Store::create([
            'name' => $data['store_name'],
            'description' => $data['store_description'],
            'rif_path' => $data['store_rif_path'],
            'certificate_of_incorporation_path' => $data['certificate_of_incorporation_path'],
            'owner_id' => $user->id, // Asociar la tienda al usuario
            'address' => $data['address_store'],
        ]);

        // Asociar la tienda al usuario en la relación many-to-many
        $user->stores()->attach($store->id);

        // Devolver la respuesta de registro estándar de Filament
        return $this->registered($user);
    }

    protected function registered(User $user): RegistrationResponse|null
    {
        return app(RegistrationResponse::class);
    }

}
