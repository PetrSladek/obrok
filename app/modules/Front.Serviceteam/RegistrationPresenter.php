<?php

namespace App\Module\Front\Serviceteam\Presenters;

use App\Forms\IServiceteamRegistrationFormFactory;
use App\Forms\ServiceteamRegistrationForm;
use App\FrontBasePresenter;
use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Person;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Module\Front\Presenters\UnspecifiedPersonAuthBasePresenter;
use App\ServiceteamBasePresenter;
use App\Model\Entity\Serviceteam;
use App\Forms\Form;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

/**
 * Class RegistrationPresenter
 * @package App\Module\Front\Serviceteam\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class RegistrationPresenter extends UnspecifiedPersonAuthBasePresenter
{

	/**
	 * @var IServiceteamRegistrationFormFactory @inject
	 */
	public $serviceteamRegistrationFormFactory;


	/**
	 * Formulář pro registraci Servisáka (převedení nespecifikovaného na ST)
	 *
	 * @return ServiceteamRegistrationForm
	 */
	protected function createComponentFrmRegistration()
	{
		$control = $this->serviceteamRegistrationFormFactory->create($this->me->id);

		$control->onServiceteamRegistered[] = function ($control, Serviceteam $me)
		{

			// $me === $this->me
			$mail = $this->emails->create('serviceteamFirstInfo', 'První informace');
			$mail->addTo($me->email);
			$this->emails->send($mail);

			// Zmenila se mi role
			$this->user->login($me->toIdentity());

			$this->flashMessage('Byl jsi úspěšně zařazen do Servisteamu', 'success');
			$this->redirect('Homepage:additional');

		};

		return $control;
	}

}


;
