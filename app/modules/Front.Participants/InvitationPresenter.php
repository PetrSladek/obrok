<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 28.11.2016
 * Time: 22:02
 */

namespace App\Module\Front\Participants\Presenters;


use App\Model\Entity\Group;
use App\Model\Entity\Person;
use App\Model\Repositories\GroupsRepository;
use App\Module\Front\Presenters\FrontBasePresenter;

class InvitationPresenter extends FrontBasePresenter
{
    /**
     * @var GroupsRepository
     * @inject
     */
    public $groups;

    /** @var Group */
    public $group;


    /**
     * Před spuštěním
     */
    public function startup()
    {
        parent::startup();

        // Pokud je ucatnik => presmerujeme na jeho Homepage
        if ($this->user->isInRole(Person::TYPE_PARTICIPANT))
        {
            $this->flashMessage('Už jsi zaregistrovaný jako účastník. Nemůžeš se registrovat znovu!', 'danger');
            $this->redirect(':Front:Participants:Homepage:');
        }
        // Pokud je servisak => presmerujeme na jeho Homepage
        elseif ($this->user->isInRole(Person::TYPE_SERVICETEAM))
        {
            $this->flashMessage('Už jsi zaregistrovaný jako servisák. Nemůžeš se registrovat znovu!', 'danger');
            $this->redirect(':Front:Serviceteam:Homepage:');
        }

    }


    /**
     * Registrace do existující skupiny (z pozvánky nebo z presmerování po zalození skupiny)
     *
     * @param $id
     * @param $hash
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function actionToGroup($id, $hash)
    {
        $this->group = $this->groups->find($id);

        if (!$this->group)
        {
            $this->error("Skupina #$id neexistuje");
        }

        if ($this->group->getInvitationHash($this->config->hashKey) !== $hash)
        {
            $this->error("Pokus o napadeni");
        }

        if ($this->user->isInRole(Person::TYPE_UNSPECIFIED))
        {
            $this->redirect(':Front:Participants:Registration:toGroup', $id, $hash);
        }

        if (!$this->openRegistrationParticipants/* && !$this->group->getFreePlaces()*/) {
            $this->flashMessage('Nelze registrovat nové učastníky. Kapacita je již zaplněná', 'warning');
            $this->redirect(':Front:Unspecified:');
        }



        $this->template->group = $this->group;
    }


}
