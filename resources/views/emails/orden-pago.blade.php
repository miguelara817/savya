@extends('emails.layouts.savya', ['title' => 'Información de pago', 'subtitle' => 'Pedido '.$orden->numero])

@section('content')
    <h2>1. Aprobación del pedido</h2>
    <p>Su pedido ha sido aprobado. Verifique cuidadosamente los datos antes de proceder con el pago. Recuerde que su pedido tiene una validez de <strong>48 horas</strong> a partir de su aprobación.</p>

    <h2>2. Datos para realizar el pago</h2>
    <p>Para confirmar su pedido, realice el depósito o transferencia bancaria a la siguiente cuenta:</p>
    <p class="email-highlight"><strong>BANCO UNIÓN S.A.</strong><br>
        Titular: INSUMOS BOLIVIA - FONDO PARA LA SEGURIDAD ALIMENTARIA<br>
        N.º de cuenta: <strong>10000008054938</strong>
    </p>

    <h2>3. Envío del comprobante de pago</h2>
    <p>Una vez realizado el pago, cargue en la plataforma el comprobante de depósito o transferencia (voucher), incluyendo el número de transacción, para proceder con la validación y confirmación de su pedido.</p>

    <p>¡Gracias por su preferencia!</p>
@endsection
