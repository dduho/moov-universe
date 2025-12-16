<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailTestController extends Controller
{
    /**
     * Tester la connexion SMTP
     */
    public function testSmtpConnection()
    {
        try {
            // Vérifier que les variables d'environnement sont configurées
            $mailHost = config('mail.mailers.smtp.host');
            $mailPort = config('mail.mailers.smtp.port');
            $mailFrom = config('mail.from.address');

            if (empty($mailHost) || empty($mailPort) || empty($mailFrom)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration SMTP incomplète',
                    'details' => 'Veuillez configurer MAIL_HOST, MAIL_PORT et MAIL_FROM_ADDRESS dans le fichier .env'
                ], 400);
            }

            // Tester la connexion en tentant d'ouvrir une socket
            $errno = 0;
            $errstr = '';
            $timeout = 5;
            
            $socket = @fsockopen($mailHost, $mailPort, $errno, $errstr, $timeout);
            
            if (!$socket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de se connecter au serveur SMTP',
                    'details' => "Erreur {$errno}: {$errstr}"
                ], 500);
            }
            
            fclose($socket);

            return response()->json([
                'success' => true,
                'message' => 'Serveur SMTP configuré et accessible',
                'config' => [
                    'host' => $mailHost,
                    'port' => $mailPort,
                    'from' => $mailFrom,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur test SMTP: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Impossible de se connecter au serveur SMTP',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
