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

        // Validaciones adicionales y notificaciones
        if (!$this->validateAndNotify($data)) {
            return null; // Detener flujo si hay errores
        }

        // Crear una acción de inicio de sesión con Magic Link
        $user = User::where('email', $data['email'])->first();
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
     * Valida el correo electrónico y muestra notificaciones en caso de error.
     */
    protected function validateAndNotify(array $data): bool
    {
        $errors = false;

        // Validar si el campo de correo electrónico está vacío
        if (empty($data['email'])) {
            Notification::make()
                ->title('Error de validación')
                ->body('El campo de correo electrónico es obligatorio.')
                ->danger()
                ->send();
            $errors = true;
        }

        // Validar formato de correo electrónico
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title('Error de validación')
                ->body('El correo electrónico no tiene un formato válido.')
                ->danger()
                ->send();
            $errors = true;
        }

        // Validar si el correo existe en la base de datos
        if (!User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title('Correo no encontrado')
                ->body('El correo electrónico ingresado no está registrado en el sistema.')
                ->warning()
                ->send();
            $errors = true;
        }

        return !$errors; // Retorna true si no hay errores
    }

    /**
     * Personaliza la excepción de validación en caso de error.
     */
    protected function throwFailureValidationException(): never
    {
        Notification::make()
            ->title('Error')
            ->body('El correo electrónico no está registrado o no es válido.')
            ->danger()
            ->send();

        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => 'El correo electrónico no está registrado en el sistema.',
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
