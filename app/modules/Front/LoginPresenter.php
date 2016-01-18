<?php

namespace App\Module\Front\Presenters;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Person;
use Kdyby\Doctrine\EntityManager;
use Nette;

/**
 * Class LoginPresenter
 * @package App\Module\Front\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class LoginPresenter extends FrontBasePresenter
{

	/** @var @persistent */
	public $back;

	/** @var \PetrSladek\SkautIS\SkautIS @inject */
	public $skautis;

	/** @var SkautisHydrator @inject */
	public $skautisHydratyor;

	/** @var EntityManager @inject */
	public $em;


	public function actionDefault()
	{

		if ($this->user->isInRole(Person::TYPE_PARTICIPANT))
		{
			$this->redirect(':Front:Participants:Homepage:');
		}
		elseif ($this->user->isInRole(Person::TYPE_SERVICETEAM))
		{
			$this->redirect(':Front:Serviceteam:Homepage:');
		}
		elseif ($this->user->isInRole(Person::TYPE_UNSPECIFIED))
		{
			$this->redirect(':Front:Unspecified:');
		}

	}

}
