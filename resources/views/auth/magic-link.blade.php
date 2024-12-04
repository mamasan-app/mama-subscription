<!-- resources/views/auth/magic-link.blade.php -->
@extends('layouts.app')

@section('content')
<h2>Inicio de sesión sin contraseña</h2>

<form action="{{ route('magiclink.send') }}" method="POST">
    @csrf
    <label for="email">Correo electrónico:</label>
    <input type="email" name="email" required>
    <button type="submit">Enviar enlace de acceso</button>
</form>

@if (session('message'))
    <p>{{ session('message') }}</p>
@endif
@endsection