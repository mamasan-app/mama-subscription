<?php

declare(strict_types=1);

namespace App\Filament\Store\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class HelpAction
{
    /**
     * Create a help action as an icon button for the Store dashboard.
     */
    public static function iconButton(string $name = 'help'): Action
    {
        return Action::make($name)
            ->modalHeading('Ayuda para Tienda')
            ->slideOver() // Usar una ventana deslizable
            ->iconButton() // Especifica que es un botón con ícono
            // ->icon('alert-circle') // Asegúrate de tener este ícono disponible en tu proyecto
            ->modalWidth(MaxWidth::TwoExtraLarge) // Tamaño de la ventana modal
            ->modalSubmitAction(false) // Deshabilitar el botón de submit en la ayuda
            ->modalCancelAction(false); // Deshabilitar el botón de cancelar en la ayuda
    }

    /**
     * Create a help action as a regular button for the Store dashboard.
     */
    public static function make(string $name = 'help'): Action
    {
        return Action::make($name)
            ->label('Ayuda')
            ->color('info') // Color de botón informativo
            ->modalHeading('Ayuda para Tienda')
            ->slideOver() // Mostrar como una ventana deslizable
            ->modalWidth(MaxWidth::TwoExtraLarge) // Tamaño grande para el modal
            ->modalSubmitAction(false) // Deshabilitar el submit
            ->modalCancelAction(false); // Deshabilitar el cancelar
    }
}
