<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cancel-expired-reservations',
    description: 'Annule les réservations en attente non confirmées après 3 heures.',
)]
class CancelExpiredReservationsCommand extends Command
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $expiryTime = new \DateTimeImmutable('-3 hours');

        $expiredReservations = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.statut = :statut')
            ->andWhere('r.createdAt < :expiryTime')
            ->setParameter('statut', 'en_attente')
            ->setParameter('expiryTime', $expiryTime)
            ->getQuery()
            ->getResult();

        $count = count($expiredReservations);

        if ($count > 0) {
            foreach ($expiredReservations as $reservation) {
                $reservation->setStatut('annule');
            }
            $this->entityManager->flush();
            $io->success(sprintf('%d réservation(s) annulée(s).', $count));
        } else {
            $io->info('Aucune réservation expirée.');
        }

        return Command::SUCCESS;
    }
}
