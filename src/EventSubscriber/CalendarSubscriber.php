<?php
namespace App\EventSubscriber;

use App\Repository\LivraisonRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $repo;
    private $security;

    public function __construct(LivraisonRepository $repo, Security $security)
    {
        $this->repo = $repo;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Le bundle déclenche cet événement tout seul
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $user = $this->security->getUser();
        if (!$user) return;

        // On filtre par livreur directement ici
        $livraisons = $this->repo->findBy(['livreur' => $user]);

        foreach ($livraisons as $livraison) {
            if (!$livraison->getDatelivraison()) continue;

            $event = new Event(
                "Livraison #" . $livraison->getId(), 
                $livraison->getDatelivraison()
            );

            // Options de design directes
            $event->setOptions([
                'backgroundColor' => '#8b5e4a',
                'borderColor' => 'transparent',
                'description' => $livraison->getAddresslivraison()
            ]);

            $calendar->addEvent($event);
        }
    }
}