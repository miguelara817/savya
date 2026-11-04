<?php

namespace App\Services;

use App\Models\ServiceDataInfo;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenWaService
{
    public function enviarMensaje(string $numero, string $contenido): Response
    {
        // $openwaData = ServiceDataInfo::where([
        //     'service' => 'OpenWA',
        //     'is_active' => true,
        //     'deleted_at' => null
        // ])
        // ->get(['base_url', 'api_key', 'session_id', 'api_key_header']);

        $openwaData = (ServiceDataInfo::where('service', 'OpenWA')
            ->where('is_active', true)
            ->get([
                'base_url',
                'api_key',
                'session_id',
                'api_key_header'
            ]))->toArray()[0];

        // dump($openwaData);

        // $baseUrl = rtrim((string) config('services.openwa.base_url'), '/');
        $baseUrl = rtrim((string) $openwaData['base_url']);

        // $sessionId = (string) config('services.openwa.session_id');
        $sessionId = (string) $openwaData['session_id'];

        // $endpoint = ltrim((string) config('services.openwa.endpoint'), '/');
        $endpoint = $baseUrl.'/api/sessions/'.$sessionId.'/messages/send-text';
 
        $apiKey = (string) $openwaData['api_key'];

        if ($baseUrl === '' || $endpoint === '' || $apiKey === '' || $sessionId === '') {
            throw new RuntimeException('La configuración de OpenWA está incompleta.');
        }

        if (trim($numero) === '' || trim($contenido) === '') {
            throw new InvalidArgumentException('El número y el contenido del mensaje son obligatorios.');
        }

        // $header = (string) config('services.openwa.api_key_header');
        $header = (string) $openwaData['api_key_header'];


        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);

        if ($header !== '') {
            $request->withHeader($header, $apiKey);
        }

        return $request
            ->post($endpoint, [
                (string) config('services.openwa.phone_field') => '591'.$numero.'@c.us',
                (string) config('services.openwa.message_field') => $contenido,
            ])
            ->throw();
    }
}
