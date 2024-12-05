<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Auth;

class EditProfile extends BaseEditProfile
{
    /**
     * Configura el esquema del formulario de edición de perfil.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->default(fn() => Auth::user()->first_name), // Cargar el nombre del usuario autenticado

                TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255)
                    ->default(fn() => Auth::user()->last_name), // Cargar el apellido

                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->unique('users', 'email', fn() => Auth::id()) // Validar que el email no esté duplicado
                    ->default(fn() => Auth::user()->email),

                TextInput::make('phone_number')
                    ->label('Número de Teléfono')
                    ->tel()
                    ->maxLength(20)
                    ->unique('users', 'phone_number', fn() => Auth::id())
                    ->default(fn() => Auth::user()->phone_number),

                TextInput::make('identity_document')
                    ->label('Cédula de Identidad')
                    ->maxLength(20)
                    ->unique('users', 'identity_document', fn() => Auth::id())
                    ->default(fn() => Auth::user()->identity_document),

                DatePicker::make('birth_date')
                    ->label('Fecha de Nacimiento')
                    ->default(fn() => Auth::user()->birth_date),

                $this->getPasswordFormComponent(), // Contraseña
                $this->getPasswordConfirmationFormComponent(), // Confirmación de Contraseña
            ]);
    }

    /**
     * Sobrescribe el método para manejar la lógica de guardar los datos del perfil.
     */
    public function save(): void
    {
        $data = $this->form->getState();

        // Actualizar los datos del usuario autenticado
        $user = Auth::user();
        $user->fill($data); // Llena los campos con la información del formulario
        $user->save();

        $this->notify('success', 'Perfil actualizado correctamente.');
    }
}
