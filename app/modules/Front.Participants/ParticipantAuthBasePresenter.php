<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Participant;
use App\Model\Entity\Person;

abstract class ParticipantAuthBasePresenter extends  \App\Module\Front\Presenters\FrontBasePresenter
{

    /** @var Participant */
    public $me;


    protected $open = true;

    public function startup () {

        parent::startup();

        if(!$this->user->isLoggedIn() || !$this->user->isInRole(Person::TYPE_PARTICIPANT)) {
            if($this->link('this') != $this->link("Homepage:")) {
                $this->flashMessage("Musíte být přihlášení");
                $this->redirect(":Front:Login:", array('back'=> $this->storeRequest()));
            }
            $this->redirect(":Front:Login:");
        }

        /** @var Participant */
        $me = $this->participants->find( $this->getUser()->getId() );
        $this->me = $me;
        $this->template->me = $this->me;


        // Uzavreni registrace, aby se v prubehu obroku{stavecky uz nedfali menit yadna data ye stranz ucastniku
        $this->open = true;
        $this->template->open = $this->open;

    }



}
