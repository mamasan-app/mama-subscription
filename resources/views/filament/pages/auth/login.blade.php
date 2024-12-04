@extends('filament::layouts.base') {{-- Usa un layout base de Filament --}}

@section('title', 'Iniciar sesión')

@section('content')
<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center">Iniciar sesión</h2>

        @if (session('message'))
            <div class="mt-4 p-2 bg-green-100 text-green-700 rounded">
                {{ session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="submit" method="POST">
            {{ $this->form }}

            <div class="mt-6">
                <button type="submit" class="bg-indigo-500 text-white py-2 px-4 rounded w-full">
                    Enviar enlace mágico
                </button>
            </div>
        </form>

        <div class="mt-4 text-center text-sm text-gray-600">
            Lea los términos y condiciones de uso <a href="https://mamapay.test" target="_blank"
                class="underline text-primary-600 hover:text-primary-500">aquí</a>
        </div>
    </div>
</div>
@endsection