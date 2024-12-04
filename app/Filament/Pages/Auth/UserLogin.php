<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseUserLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use App\Models\User;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;
use App\Notifications\MagicLinkNotification;

class UserLogin extends BaseUserLogin
{
    /**
     * Define el formulario que contiene únicamente el campo de correo electrónico.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data'); // Path para almacenar los datos del formulario
    }

    /**
     * Sobreescribir el método authenticate para manejar el envío de Magic Link.
     */
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        // Validar que el correo exista en la base de datos
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $this->throwFailureValidationException();
        }

        // Crear una acción de inicio de sesión con Magic Link
        $action = new LoginAction($user);

        // Crear el enlace mágico
        $magicLinkUrl = MagicLink::create($action)->url;

        // Enviar el enlace mágico al correo del usuario
        $user->notify(new MagicLinkNotification($magicLinkUrl));

        // Mensaje de éxito
        session()->flash('message', 'Se ha enviado un enlace de acceso a tu correo.');

        Notification::make()
            ->title('¡Enlace enviado!')
            ->body('Se ha enviado un enlace mágico a tu correo electrónico. Revisa tu bandeja de entrada para continuar.')
            ->success()
            ->send();


        // Retorna null ya que no se realizará una autenticación directa
        return null;
    }

    /**
     * Personaliza la excepción de validación en caso de error.
     */
    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => 'El correo no está registrado en el sistema.',
        ]);
    }

    /**
     * Sobreescribe el método que define las acciones del formulario.
     */
    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('sendMagicLink')
            ->label('Enviar Enlace Mágico')
            ->submit('authenticate');
    }

    /**
     * Define las acciones personalizadas del formulario.
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Redefine el componente del formulario para solo incluir el correo electrónico.
     */
    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Correo Electrónico')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * Maneja el submit del formulario para llamar al método de autenticación.
     */
    public function submit()
    {
        $this->authenticate();
    }
}
