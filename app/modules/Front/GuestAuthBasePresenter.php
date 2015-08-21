<?php

namespace App\Module\Front\Presenters;;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Unspecified;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use PetrSladek\SkautIS\Dialog\LoginDialog;
use PetrSladek\SkautIS\SkautIS;


abstract class GuestAuthBasePresenter extends \App\Module\Base\Presenters\BasePresenter
{
    /** @var Unspecified */
    public $me;

    /** @var PersonsRepository @inject  */
    public $persons;

    public function startup () {
        parent::startup();

        // Kdyz neni prihlaseny vubec => donutime ho se prihlasit
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect(":Front:Login:", array('back'=> $this->storeRequest()));
        }
        // Pokud je ucatnik => presmerujeme na jeho Homepage
        elseif($this->user->isInRole(Person::TYPE_PARTICIPANT)) {
            $this->flashMessage('Už si zaregistrovaný jako účastník. Nemůžeš se registrovat znovu!', 'danger');
            $this->redirect(':Front:Participants:Homepage:');
        }
        // Pokud je servisak => presmerujeme na jeho Homepage
        elseif($this->user->isInRole(Person::TYPE_SERVICETEAM)) {
            $this->flashMessage('Už si zaregistrovaný jako servisák. Nemůžeš se registrovat znovu!', 'danger');
            $this->redirect(':Front:Serviceteam:Homepage:');
        }


        /** @var Unspecified */
        $me = $this->persons->find( $this->getUser()->getId() );
        $this->me = $me;
        $this->template->me = $this->me;
    }


}
