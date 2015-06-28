<?php

namespace App\Module\Front\Presenters;
use App\Repositories\GroupsRepository;


class MapPresenter extends FrontBasePresenter
{


    /** @var GroupsRepository @inject */
    public $groups;

    public function renderDefault() {
        $list = $this->groups->findBy(['canceled' => false]);

        $this->template->groups = $list;
    }


};
