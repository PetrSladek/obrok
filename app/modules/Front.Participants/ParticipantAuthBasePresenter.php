<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Participant;

abstract class ParticipantAuthBasePresenter extends  \App\Module\Front\Presenters\FrontBasePresenter
{

    /** @var Participant */
    public $me;


    protected $open = true;

    public function startup () {

        parent::startup();

        if(!$this->user->isLoggedIn()) {
            if($this->link('this') != $this->link("Homepage:")) {
                $this->flashMessage("Musíte být přihlášení");
                $this->redirect(":Login:", array('back'=> $this->storeRequest()));
            }
            $this->redirect(":Login:");
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
