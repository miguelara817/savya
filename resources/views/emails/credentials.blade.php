
@extends('emails.layouts.savya', ['title' => 'Tus credenciales de acceso'])

@section('content')
    <p>Hola,</p>

    <p>Estas son tus credenciales de acceso:</p>

    <p class="email-highlight">
        <strong>Usuario:</strong>
        {{ $username }}
    </p>

    <p class="email-highlight">
        <strong>Contraseña:</strong>
        {{ $password }}
    </p>

    {{-- <p>Por seguridad, cambia tu contraseña después de ingresar.</p> --}}
@endsection
