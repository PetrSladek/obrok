<?php

namespace App\Module\Base\Presenters;

use App\Model\Phone;
use Kdyby\Doctrine\EntityManager;
use App\Services\EmailsService;
use App\Services\ImageService;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use PetrSladek\SkautIS\SkautIS;


abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

    /** @var ArrayHash */
	protected $config;

    /** @var ImageService @inject */
    public $images;

    /** @var EmailsService @inject */
    public $emails;

    /** @var EntityManager @inject */
    public $em;


    public $ageInDate;

    /** @var SkautIS @inject */
    public $skautis;

//    /** @var SettingsRepository @inject */
//    public $settings;

//    /** @var Connection @inject */
//    public $database;

    /**
     * Je povoleno registrovat nové učastníky?
     * @var bool
     */
    public $openRegistrationParticipants;

    /**
     * Je povoleno registrovat nové Servisaky?
     * @var bool
     */
    public $openRegistrationServiceteam;


    const OPEN_PARTICIPANTS_REGISTRATION_KEY = 'openRegistrationParticipants';

    const OPEN_SERVICETEAM_REGISTRATION_KEY = 'openRegistrationServiceteam';


    protected function startup()
	{
		parent::startup();
//        $this->skautis->setStorage($_SESSION["__" . __CLASS__] = []);

		$this->config = ArrayHash::from( $this->context->parameters['app'] );

        // Sablona pro emaily
        $this->emails->setTemplate($this->createTemplate());


        $this->ageInDate = DateTime::from('8.6.2015');
        $this->template->ageInDate = $this->ageInDate;



        $this->openRegistrationParticipants = true; //$this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true); // default TRUE
        $this->template->openRegistrationParticipants = $this->openRegistrationParticipants;

        $this->openRegistrationServiceteam = true; //$this->settings->get(self::OPEN_SERVICETEAM_REGISTRATION_KEY, true); // default TRUE
        $this->template->openRegistrationServiceteam = $this->openRegistrationServiceteam;
	}



	public function beforeRender() {

		// Helpers
		$this->template->registerHelper('timeAgoInWords' , 'Helpers::timeAgoInWords');
		$this->template->registerHelper('month' , 'Helpers::month');
		$this->template->registerHelper('date' , $this->date);
		$this->template->registerHelper('implode' , $this->implode);
		$this->template->registerHelper('monthName' , $this->monthName);
		$this->template->registerHelper('bbcolumns' , $this->bbcolumns);
        $this->template->registerHelper('day' , $this->day);
        $this->template->registerHelper('phone' , $this->phone);

		// Variables
		$this->template->config = $this->config;
		$this->template->storageUrl = $this->config->storageUrl;

		$this->template->user = $this->getUser();
	}


	public function monthName($month) {
		$months = array(
			'-',
			'leden',
			'únor',
			'březen',
			'duben',
			'květen',
			'červen',
			'červenec',
			'srpen',
			'září',
			'říjen',
			'listopad',
			'prosinec',
		);
		return isset($months[$month]) ? $months[$month] : $months[0];
	}
	public function implode($array, $glue = ',') {
		return implode($glue, $array);
	}

	public function date($date,$format='j.n.Y') {
		$date = \Nette\DateTime::from($date);
		return $date->format($format);
	}

    public function phone($phone) {
        if(!($phone instanceof Phone))
            $phone = new Phone($phone);

        return Html::el(null)->add( Html::el('small class="cc"')->setText($phone->getCc()) )->add( Html::el(null)->setText(" ".$phone->getNumber()) );
    }




	public function bbcolumns($input) {

		// [one_third]textik[/one_third]

		$bb['one_third'] = '<div class="one_third bbcol">#</div>';
		$bb['one_third_last'] = '<div class="one_third column-last bbcol">#</div><div class="clear"></div>';
		$bb['two_third'] = '<div class="two_third bbcol">#</div>';
		$bb['two_third_last'] = '<div class="two_third column-last bbcol">#</div><div class="clear"></div>';
		$bb['one_half'] = '<div class="one_half bbcol">#</div>';
		$bb['one_half_last'] = '<div class="one_half column-last bbcol">#</div><div class="clear"></div>';
		$bb['one_fourth'] = '<div class="one_fourth bbcol">#</div>';
		$bb['one_fourth_last'] = '<div class="one_fourth column-last bbcol">#</div><div class="clear"></div>';
		$bb['three_fourth'] = '<div class="three_fourth bbcol">#</div>';
		$bb['three_fourth_last'] = '<div class="three_fourth column-last bbcol">#</div><div class="clear"></div>';
		$bb['one_fifth'] = '<div class="one_fifth bbcolh">#</div>';
		$bb['one_fifth_last'] = '<div class="one_fifth column-last bbcol">#</div><div class="clear"></div>';
		$bb['two_fifth'] = '<div class="two_fifth bbcol">#</div>';
		$bb['two_fifth_last'] = '<div class="two_fifth column-last bbcol">#</div><div class="clear"></div>';
		$bb['three_fifth'] = '<div class="three_fifth bbcol">#</div>';
		$bb['three_fifth_last'] = '<div class="three_fifth column-last bbcol">#</div><div class="clear"></div>';
		$bb['four_fifth'] = '<div class="four_fifth bbcol">#</div>';
		$bb['four_fifth_last'] = '<div class="four_fifth column-last bbcol">#</div><div class="clear"></div>';
		$bb['one_sixth'] = '<div class="one_sixth bbcol">#</div>';
		$bb['one_sixth_last'] = '<div class="one_sixth column-last bbcol">#</div><div class="clear"></div>';
		$bb['five_sixth'] = '<div class="five_sixth bbcol">#</div>';
		$bb['five_sixth_last'] = '<div class="five_sixth column-last bbcol">#</div><div class="clear"></div>';

		/** @see http://forrst.com/posts/Simple_PHP_BBCode_Parser-N0z */
/*
		$match["b"] = "/\[b\](.*?)\[\/b\]/is";
		$replace["b"] = "<b>$1</b>";
		$match["i"] = "/\[i\](.*?)\[\/i\]/is";
		$replace["i"] = "<i>$1</i>";
*/

		$match = array();
		$replace = array();

		foreach($bb as $key => $replacing) {
			$match[$key] = "/\[$key\](.*?)\[\/$key\]/is";
			$replace[$key] = str_replace('#','$1',$replacing);
		}

		return preg_replace($match, $replace, $input);


	}



	public function flashMessageFormatted($type, $format, $args=null) {
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		return $this->flashMessage(vsprintf($format, $args), $type);
	}



    public function day(\DateTime $datetime) {
        $day = $datetime->format('N');
        $days=['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'];

        return @$days[$day];
    }



    public function getParticipantProgram($id) {
//        $programs = $this->database->query("
//          SELECT
//            p.id as program_id,
//            b.id as block_id,
//            p.start, DATE_ADD(p.start, INTERVAL b.duration*15 MINUTE) as end,
//            b.duration, b.name, b.tools, b.location, b.perex, b.lectorExt as lector,
//            b.capacity,
//            (SELECT COUNT(*) FROM program_user WHERE program_id = p.id) as occupied
//          FROM program_user pu
//          JOIN program p ON (p.id = pu.program_id  AND pu.participant_id = ?)
//          JOIN block b ON b.id = p.block_id
//          ORDER BY start ASC, end ASC
//        ", $id)->fetchAll();
//
//        foreach($programs as &$program) {
//            if($program->start->format('Y-m-d H:i') == "2015-06-09 17:00") {
//                $program->programSection = 'Cesta';
//                $program->programSubSection = null;
//            } else if($program->start == DateTime::from("2015-06-11 8:00")) {
//                $program->programSection = 'Služba';
//                $program->programSubSection = null;
//            } else if($program->start >= DateTime::from("2015-06-11 13:45") && $program->start <= DateTime::from("2015-06-11 18:30")) {
//                $program->programSection = 'Živly';
//                $program->programSubSection = null;
//            } else if($program->start->format('Y-m-d H:i') == "2015-06-13 09:30") {
//                $program->programSection = 'Vapro';
//                $program->programSubSection = '1. blok';
//            } else if($program->start->format('Y-m-d H:i') == "2015-06-13 11:30") {
//                $program->programSection = 'Vapro';
//                $program->programSubSection = '2. blok';
//            } else if($program->start->format('Y-m-d H:i') == "2015-06-13 13:15") {
//                $program->programSection = 'Vapro';
//                $program->programSubSection = 'Obědový meziblok';
//            } else if($program->start->format('Y-m-d H:i') == "2015-06-13 15:00") {
//                $program->programSection = 'Vapro';
//                $program->programSubSection = '3. blok';
//            } else if($program->start->format('Y-m-d H:i') == "2015-06-13 17:00") {
//                $program->programSection = 'Vapro';
//                $program->programSubSection = '4. blok';
//            }
//        }

//        return $programs;

        return [];
    }




    public function handleLogout() {

        $this->getUser()->logout();

        $this->flashMessage("Odhlášení proběhlo úspěšně");
        $this->redirect('this');
    }

}
