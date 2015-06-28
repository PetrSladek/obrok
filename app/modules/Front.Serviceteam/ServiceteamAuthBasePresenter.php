<?php

namespace  App\Module\Front\Serviceteam\Presenters;

use App\Model\Entity\Serviceteam;

abstract class ServiceteamAuthBasePresenter extends \App\Module\Front\Presenters\FrontBasePresenter
{

    /** @var Serviceteam */
    public $me;

    public $repository;

    public function startup () {
        parent::startup();

        $this->repository = $this->em->getRepository(Serviceteam::class);

        if(!$this->getUser()->isLoggedIn()) {
            if($this->link('this') != $this->link("Homepage:")) {
                $this->flashMessage("Musíte být přihlášení");
                $this->redirect(":Login:", array('back'=> $this->storeRequest()));
            }
            $this->redirect(":Login:");
        }

        /** @var Serviceteam */
        $me = $this->repository->find( $this->getUser()->getId() );
        $this->me = $me;
        $this->template->me = $this->me;
    }


}
