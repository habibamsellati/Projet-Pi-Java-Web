<?php

namespace App\Command;

use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:check-pending-reclamations',
    description: 'Check for reclamations pending for more than 48 hours and send email notifications every 5 minutes',
)]
class CheckPendingReclamationsCommand extends Command
{
    private ReclamationRepository $reclamationRepository;
    private MailerInterface $mailer;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ReclamationRepository $reclamationRepository,
        MailerInterface $mailer,
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->reclamationRepository = $reclamationRepository;
        $this->mailer = $mailer;
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get reclamations pending for more than 48 hours
        $pendingReclamations = $this->reclamationRepository->findPendingOver48Hours();

        if (empty($pendingReclamations)) {
            $io->success('No pending reclamations over 48 hours.');
            return Command::SUCCESS;
        }

        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'sbaiemna04@gmail.com';
        $mailFrom = $_ENV['MAIL_FROM'] ?? 'sbaiemna04@gmail.com';
        $emailsSent = 0;

        foreach ($pendingReclamations as $reclamation) {
            try {
                // Check if we should send notification (every 5 minutes or first time)
                $lastNotification = $reclamation->getLastNotificationSent();
                $now = new \DateTime();
                
                if ($lastNotification === null) {
                    // First notification
                    $shouldSend = true;
                } else {
                    // Check if 5 minutes have passed since last notification
                    $interval = $lastNotification->diff($now);
                    $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                    $shouldSend = $minutesPassed >= 5;
                }

                if (!$shouldSend) {
                    $io->writeln(sprintf(
                        'Skipping reclamation #%d (last notification sent recently)',
                        $reclamation->getId()
                    ));
                    continue;
                }

                $user = $reclamation->getUser();
                $hoursWaiting = $this->getHoursWaiting($reclamation);

                $email = (new Email())
                    ->from($mailFrom)
                    ->to($adminEmail)
                    ->subject('⚠️ Réclamation en attente depuis plus de 48h - #' . $reclamation->getId())
                    ->html($this->generateEmailContent($reclamation, $hoursWaiting));

                $this->mailer->send($email);

                // Update last notification time
                $reclamation->setLastNotificationSent($now);
                $this->entityManager->flush();

                $emailsSent++;

                $io->writeln(sprintf(
                    'Email sent for reclamation #%d (waiting for %d hours)',
                    $reclamation->getId(),
                    $hoursWaiting
                ));
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to send email for reclamation #%d: %s',
                    $reclamation->getId(),
                    $e->getMessage()
                ));
            }
        }

        if ($emailsSent > 0) {
            $io->success(sprintf('Sent %d email notifications.', $emailsSent));
        } else {
            $io->info('No new notifications to send (all reclamations notified within last 5 minutes).');
        }

        return Command::SUCCESS;
    }

    private function getHoursWaiting($reclamation): int
    {
        $now = new \DateTime();
        $created = $reclamation->getDatecreation();
        $interval = $created->diff($now);
        return ($interval->days * 24) + $interval->h;
    }

    private function generateEmailContent($reclamation, int $hoursWaiting): string
    {
        $user = $reclamation->getUser();
        $userName = $user ? $user->getPrenom() . ' ' . $user->getNom() : 'Inconnu';
        $userEmail = $user ? $user->getEmail() : 'N/A';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
        .info-row { margin: 8px 0; }
        .label { font-weight: bold; color: #495057; }
        .value { color: #212529; }
        .description { background: white; padding: 15px; margin: 15px 0; border-radius: 4px; border: 1px solid #dee2e6; }
        .button { display: inline-block; padding: 12px 24px; background: #8b5e4a; color: white; text-decoration: none; border-radius: 6px; margin-top: 15px; }
        .footer { text-align: center; padding: 15px; color: #6c757d; font-size: 12px; }
        .urgent { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">⚠️ Réclamation en attente</h2>
            <p style="margin: 5px 0 0 0;">Action requise - Délai dépassé</p>
        </div>
        
        <div class="content">
            <p class="urgent">Cette réclamation est en attente depuis {$hoursWaiting} heures (plus de 48h).</p>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #dc3545;">Détails de la réclamation</h3>
                <div class="info-row">
                    <span class="label">ID:</span>
                    <span class="value">#{$reclamation->getId()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Titre:</span>
                    <span class="value">{$reclamation->getTitre()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Statut:</span>
                    <span class="value">{$reclamation->getStatut()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Date de création:</span>
                    <span class="value">{$reclamation->getDatecreation()->format('d/m/Y H:i')}</span>
                </div>
                <div class="info-row">
                    <span class="label">Temps d'attente:</span>
                    <span class="value urgent">{$hoursWaiting} heures</span>
                </div>
            </div>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #8b5e4a;">Informations client</h3>
                <div class="info-row">
                    <span class="label">Nom:</span>
                    <span class="value">{$userName}</span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value">{$userEmail}</span>
                </div>
            </div>
            
            <div class="description">
                <h3 style="margin-top: 0;">Description:</h3>
                <p>{$reclamation->getDescripition()}</p>
            </div>
            
            <a href="http://localhost:8000/back/reclamation/{$reclamation->getId()}" class="button">
                Voir et répondre à la réclamation
            </a>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par le système AfkArt.</p>
            <p>Veuillez répondre à cette réclamation dès que possible.</p>
            <p style="margin-top: 10px; font-size: 11px;">Vous recevrez un rappel toutes les 5 minutes jusqu'à ce qu'une réponse soit apportée.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
