@extends('emails.layouts.savya', ['title' => 'Verificación de inicio de sesión'])

@section('content')
    <p>Hola,</p>

    <p>Usa el siguiente código para completar tu inicio de sesión en SAVYA:</p>

    <p class="verification-code">{{ $code }}</p>

    <p>El código vence en <strong>{{ $codeExpiryMinutes }} minutos</strong>. Si no solicitaste este código, puedes ignorar este mensaje.</p>
@endsection
