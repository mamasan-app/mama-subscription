<?php

declare(strict_types=1);

namespace App\Filament\Store\Fields;

use App\Filament\Fields\FilamentInput;
use App\Models\Store;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class StoreFileUpload implements FilamentInput
{
    public static function make(string $name): FileUpload
    {
        // Configuramos el disco de almacenamiento privado
        $disk = 'stores'; // Nombre del disco definido en 'filesystem.php'
        

        return FileUpload::make($name)
            ->disk($disk) // Asignamos el disco correcto
            ->visibility('private') // Definimos que la visibilidad será privada
            ->directory(function () {
                /** @var Store $store */
                $store = Filament::getTenant(); // Obtenemos la tienda actual (tenant)
    
                // Creamos el directorio específico para cada tienda sin repetir 'stores'
                return "uploads/{$store->id}";
            })
            ->saveUploadedFileUsing(function ($file) use ($disk) {
                $store = Filament::getTenant();
                // Generamos un nombre único para el archivo
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

                // Guardamos el archivo en el disco, especificando correctamente el directorio y el nombre del archivo
                $directory = "uploads/{$store->id}"; // Ruta base del directorio
                $path = Storage::disk($disk)->putFileAs($directory, $file, $fileName);

                return $path; // Devuelve la ruta del archivo correctamente
            });
    }
}
