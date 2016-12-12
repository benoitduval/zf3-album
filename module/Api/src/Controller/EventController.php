<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractController;
use Application\Model;
use Application\TableGateway;

class EventController extends AbstractController
{

    public function exportAction()
    {
        if ($this->getUser()) {
            $events = $this->get(TableGateway\Event::class)->getActiveByUserId($this->getUser()->id);
            $calendar = new \Application\Service\Calendar($events->toArray());
            $ical = $calendar->generateICS();

            $view = new ViewModel(['ical' => $ical]);
            $view->setTerminal(true);
            $view->setTemplate('api/default/data.phtml');
            return $view;
        }
    }

    public function getAction($value='')
    {
        if ($this->getUser()) {
            $events = $this->get(TableGateway\Event::class)->getActiveByUserId($this->getUser()->id);
            $guestTable = $this->get(TableGateway\Guest::class);
            $result = [];
            $config = $this->get('config');
            foreach ($events as $event) {
                $guest = $guestTable->fetchOne([
                    'userId'  => $this->getUser()->id,
                    'eventId' => $event->id
                ]);

                if ($guest->response == Model\Guest::RESP_OK) {
                    $color = '#8BC34A';
                } else if ($guest->response == Model\Guest::RESP_NO) {
                    $color = '#F44336';
                } else if ($guest->response == Model\Guest::RESP_INCERTAIN) {
                    $color = '#FFC107';
                } else {
                    $color = '#CCC';
                }

                $eventDate = \Datetime::createFromFormat('Y-m-d H:i:s', $event->date);
                $result[]  = [
                    'title' => $event->name,
                    'start' => $eventDate->format('Y-m-d'),
                    'url'   => $config['baseUrl'] . '/event/detail/' . $event->id,
                    'color' => $color
                ];
            }

            $view = new ViewModel(['result' => $result]);
            $view->setTerminal(true);
            $view->setTemplate('api/default/json.phtml');
            return $view;
        }
    }
}