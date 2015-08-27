<?php

namespace App\Module\Front\Presenters;;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use PetrSladek\SkautIS\Dialog\LoginDialog;
use PetrSladek\SkautIS\SkautIS;


abstract class FrontBasePresenter extends \App\Module\Base\Presenters\BasePresenter
{


    /** @var ParticipantsRepository @inject */
    public $participants;

    /** @var ServiceteamRepository @inject */
    public $serviceteams;

    /** @var PersonsRepository @inject */
    public $persons;

    /** @var SkautIS @inject */
    public $skautis;

    /** @var SkautisHydrator @inject */
    public $skautisHydrator;

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

            $skautisPersonId = (int)$skautis->getPersonId();

            try {

                $person = $this->persons->findBySkautisPersonId($skautisPersonId);

                // Pokud existuje jako ucastnik, servisak nebo guest (jeste si nezvolil co bude)
                if ($person) {
                    $this->getUser()->login($person->toIdentity());
                }
                // Jinak ho zaregistruju ho jako UnspecifiedPerson person
                else {

                    // vytvori uzivatele podle skautis udaju
                    $person = new UnspecifiedPerson();
                    $this->skautisHydrator->hydrate($person, $skautisPersonId);

                    $this->em->persist($person);
                    $this->em->flush();

                    $this->getUser()->login($person->toIdentity());
                }


            } catch (\Exception $e) {
                \Tracy\Debugger::log($e, 'skautis');
                $this->flashMessage("Přihlášení se nezdařilo.");
            }


            if($this->getParameter('back'))
                $this->restoreRequest($this->getParameter('back'));
            $this->redirect('this');
        };

        return $dialog;
    }


}
