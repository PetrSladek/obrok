<?php

namespace App\Module\Front\Presenters;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Module\Base\Presenters\BasePresenter;
use PetrSladek\SkautIS\Dialog\LoginDialog;
use PetrSladek\SkautIS\SkautIS;

/**
 * Class FrontBasePresenter
 * @package App\Module\Front\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
abstract class FrontBasePresenter extends BasePresenter
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
		$dialog->onResponse[] = function (LoginDialog $dialog)
		{

			$skautis = $dialog->getSkautIS();

			if (!$skautis->isLoggedIn())
			{
				$this->flashMessage("Přihlášení se nezdařilo.");

				return;
			}

			$skautisUserId = (int) $skautis->getUserId();
			$skautisPersonId = (int) $skautis->getPersonId();

			try
			{

				$person = $this->persons->findBySkautisUserId($skautisUserId);

				// Pokud existuje jako ucastnik, servisak nebo guest (jeste si nezvolil co bude)
				if ($person)
				{

					if (!$person->getUnitNumber())
					{
						try
						{
							$membership = $this->skautis->getClient()->org->MembershipAllPerson(['ID_Person' => $skautisPersonId]);
							if (isset($membership->MembershipAllOutput->RegistrationNumber))
							{
								$unitNumber = $membership->MembershipAllOutput->RegistrationNumber;
								$person->setUnitNumber($unitNumber);

								$this->em->flush($person);
							}
						}
						catch (\Exception $e)
						{

						}
					}

					$this->getUser()->login($person->toIdentity());
				}
				// Jinak ho zaregistruju ho jako UnspecifiedPerson person
				else
				{

					// vytvori uzivatele podle skautis udaju
					$person = new UnspecifiedPerson();
					$this->skautisHydrator->hydrate($person, $skautisPersonId);

					$this->em->persist($person);
					$this->em->flush();

					$this->getUser()->login($person->toIdentity());
				}

			}
			catch (\Exception $e)
			{
				\Tracy\Debugger::log($e, 'skautis');
				$this->flashMessage("Přihlášení se nezdařilo.");
			}

			if ($this->getParameter('back'))
			{
				$this->restoreRequest($this->getParameter('back'));
			}
			$this->redirect('this');
		};

		return $dialog;
	}

}
