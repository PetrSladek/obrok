<?php

namespace App\Module\Front\Presenters;;

use App\BasePresenter;
use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Guest;
use App\Model\Entity\Person;
use Kdyby\Doctrine\EntityManager;
use Nette;


class LoginPresenter extends FrontBasePresenter
{

    /** @var \PetrSladek\SkautIS\SkautIS @inject */
    public $skautis;

    /** @var SkautisHydrator @inject */
    public $skautisHydratyor;

    /** @var EntityManager @inject */
    public $em;


    public function actionDefault() {

        if($this->user->isInRole(Person::TYPE_PARTICIPANT)) {
            $this->redirect(':Front:Participants:Homepage:');
        }
        elseif($this->user->isInRole(Person::TYPE_SERVICETEAM)) {
            $this->redirect(':Front:Serviceteam:Homepage:');
        }

    }



    public function actionRegistration() {

        if(!$this->user->isLoggedIn())
            $this->redirect('Login:');

        if($this->user->isInRole(Person::TYPE_SERVICETEAM)) {
            $this->flashMessage('Jste zaregistrován jako člen servistýmu. Podruhé se registrovat nemůžete!');
            $this->redirect(":Serviceteam:Homepage:");
        }
        if($this->user->isInRole(Person::TYPE_PARTICIPANT)) {
            $this->flashMessage('Jste zaregistrován jako účastník. Podruhé se registrovat nemůžete!');
            $this->redirect(":Serviceteam:Homepage:");
        }
    }






}
