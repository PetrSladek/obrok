<?php

namespace App\Module\Front\Presenters;;

use App\Model\Entity\Participant;
use App\Repositories\ParticipantsRepository;
use App\Repositories\ServiceteamRepository;
use PetrSladek\SkautIS\Dialog\LoginDialog;
use PetrSladek\SkautIS\SkautIS;


abstract class FrontBasePresenter extends \App\Module\Base\Presenters\BasePresenter
{


    /** @var ParticipantsRepository @inject */
    public $participants;

    /** @var ServiceteamRepository @inject */
    public $serviceteams;


    /**
     * @var SkautIS @inject
     */
    public $skautis;



    /** @return LoginDialog */
    protected function createComponentSkautisLogin()
    {
        $dialog = new LoginDialog($this->skautis);
        $dialog->onResponse[] = function (LoginDialog $dialog) {

            $skautis = $dialog->getSkautIS();

            if (!$skautis->isLoggedIn()) {
                $this->flashMessage("Přihlášení se nezdařilo.");
                return;
            }

//            try {

            // Pokud existuje jako Ucastnik
            if($participant = $this->em->getRepository(Participant::class)->findOneBy(['skautisPersonId'=> (int) $skautis->getPersonId()]) ) {
                $this->getUser()->login( $participant->toIdentity() );
            }
            // Pokud existuje jako ST
            elseif ($serviceteam = $this->em->getRepository(Serviceteam::class)->findOneBy(['skautisPersonId'=> (int) $skautis->getPersonId()])) {
                $this->getUser()->login( $serviceteam->toIdentity() );
            }
            else {
                // Prihlasim ho jako HOSTA
                $this->getUser()->login(new \Nette\Security\Identity(null, Person::ROLE_GUEST, []));
            }


//            } catch (\Exception $e) {
//                \Tracy\Debugger::log($e, 'skautis');
//                $this->flashMessage("Přihlášení se nezdařilo.");
//            }


            if($this->getParameter('back'))
                $this->restoreRequest($this->getParameter('back'));
            $this->redirect('this');
        };

        return $dialog;
    }


}
