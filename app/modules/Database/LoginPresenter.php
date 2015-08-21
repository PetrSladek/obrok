<?php

namespace App\Module\Database\Presenters;


use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ServiceteamRepository;
use Nette;
use PetrSladek\SkautIS\Dialog\LoginDialog;


class LoginPresenter extends \App\Module\Base\Presenters\BasePresenter // Neoveruje prihlaseni
{

    /** @persistent */
    public $back = null;

    /** @var Serviceteam */
    public $me;

    /**
     * @var ServiceteamRepository @inject
     */
    public $serviceteams;


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

            try {

                // Pokud existuje jako ST
                if ($serviceteam = $this->em->getRepository(Serviceteam::class)->findOneBy(['skautisPersonId'=> (int) $skautis->getPersonId()])) {
                    $this->getUser()->login( $serviceteam->toIdentity() );
                } else {
                    $this->flashMessage("Pod tímto skautis učtem neexistuje žádný ST.");
                }


            } catch (\Exception $e) {
                \Tracy\Debugger::log($e, 'skautis');
                $this->flashMessage("Přihlášení se nezdařilo.");
            }


            if($this->getParameter('back'))
                $this->restoreRequest($this->getParameter('back'));
            $this->redirect('Dashboard:');
        };

        return $dialog;
    }



    public function actionDefault() {
        if($this->user->isLoggedIn())
            $this->redirect('Dashboard:');

    }



}
