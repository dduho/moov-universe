<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email} {--subject=Test Moov Universe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer un email de test pour vérifier la configuration SMTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $subject = $this->option('subject');

        $this->info("📧 Envoi d'un email de test à {$email}...");
        $this->newLine();

        try {
            Mail::raw(
                "Bonjour,\n\n" .
                "Ceci est un email de test depuis Moov Universe.\n\n" .
                "Si vous recevez ce message, cela signifie que la configuration SMTP fonctionne correctement.\n\n" .
                "Configuration utilisée:\n" .
                "- Mailer: " . config('mail.default') . "\n" .
                "- Host: " . config('mail.mailers.smtp.host') . "\n" .
                "- Port: " . config('mail.mailers.smtp.port') . "\n" .
                "- Encryption: " . config('mail.mailers.smtp.encryption') . "\n" .
                "- From: " . config('mail.from.address') . "\n\n" .
                "Cordialement,\n" .
                "L'équipe Moov Universe",
                function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject);
                }
            );

            $this->newLine();
            $this->info("✅ Email envoyé avec succès!");
            $this->info("📬 Vérifiez votre boîte de réception (et le dossier spam).");
            $this->newLine();
            
            $this->info("Configuration utilisée:");
            $this->table(
                ['Paramètre', 'Valeur'],
                [
                    ['Mailer', config('mail.default')],
                    ['Host', config('mail.mailers.smtp.host')],
                    ['Port', config('mail.mailers.smtp.port')],
                    ['Encryption', config('mail.mailers.smtp.encryption')],
                    ['Username', config('mail.mailers.smtp.username')],
                    ['From Address', config('mail.from.address')],
                    ['From Name', config('mail.from.name')],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Erreur lors de l'envoi de l'email:");
            $this->error($e->getMessage());
            $this->newLine();
            
            $this->warn("💡 Vérifiez:");
            $this->line("  - Les paramètres SMTP dans le fichier .env");
            $this->line("  - Que le cache de configuration est vidé: php artisan config:clear");
            $this->line("  - Les logs Laravel: storage/logs/laravel.log");
            $this->line("  - Le port n'est pas bloqué par un pare-feu");
            $this->newLine();

            return Command::FAILURE;
        }
    }
}
