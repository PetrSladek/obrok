<?php

namespace App\Module\Front\Presenters;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Participant;
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


    /**
     * Přihlásí uživatele
     *
     * @param $id
     * @param $hash
     *
     * @throws Nette\Application\AbortException
     * @throws Nette\Application\BadRequestException
     * @throws Nette\Security\AuthenticationException
     */
	public function actionAs($id, $hash)
    {
        /** @var Person $person */
        $person = $this->persons->find($id);
        if (!$person)
        {
            $this->error('Uzivatel neexistuje');
        }

        if (empty($hash))
        {
            $this->error('Prazdny hash');
        }

        if (!Nette\Security\Passwords::verify($hash, $person->quickLoginHash))
        {
            $this->error('Spatne hash');
        }

        $person->quickLoginHash = null;
        $this->em->persist($person);
        $this->em->flush();

        $this->user->login($person->toIdentity());
        $this->redirect('default');
    }

}
