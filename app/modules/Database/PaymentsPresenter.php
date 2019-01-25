<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Participant;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ServiceteamRepository;
use App\Query\ParticipantsQuery;
use App\Query\ProgramsQuery;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Http\IResponse;
use Nette\Utils\MimeTypeDetector;
use Nette\Utils\Paginator;
use Nextras\Datagrid\Datagrid;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Class ProgramPresenter
 * @package App\Module\Database\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class PaymentsPresenter extends DatabaseBasePresenter
{

    /**
     * @var ParticipantsRepository
     * @inject
     */
    public $participants;

    /**
     * @var ServiceteamRepository
     * @inject
     */
    public $serviceteam;

	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();
		$this->acl->edit = $this->user->isInRole('groups-edit') && $this->user->isInRole('serviceteam-edit');
	}


	/**
	 * Továrna na komponentu formulare
	 *
	 * @return Form
	 */
	public function createComponentFrmUpload()
	{
	    $frm = new Form();

	    $frm->addUpload('file', 'XLSX soubor s importem')
//            ->addRule(Form::MIME_TYPE, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setRequired()
        ->setHtmlAttribute('accept', '.xls,.xlsx');

	    $frm->addSubmit('send', 'Odeslat');

	    $frm->onSuccess[] = [$this, 'onFrmImportSubmitted'];

		return $frm;
	}

    /**
     * @param Form $frm
     *
     * @throws \Nette\Application\AbortException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function onFrmImportSubmitted(Form $frm)
    {
        set_time_limit(0);
        $values = $frm->getValues();
        /** @var FileUpload $file */
        $file = $values->file;

        /** Load $inputFileName to a Spreadsheet Object  **/
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTemporaryFile());
        $worksheet = $spreadsheet->setActiveSheetIndex(0);

        $errors = [];
        $success = [];

        $header = null;
        foreach ($worksheet->getRowIterator() as $row)
        {
            $row = array_map(function (Cell $cell) {
                return $cell->getValue();
            }, iterator_to_array($row->getCellIterator()));

            if ($header === null)
            {
                $header = $row;
                continue;
            }

            if (empty(array_filter($row)))
            {
                continue;
            }

            $varSymbol = (int) $row['D'];
            $amount = (int) $row['G'];
            $fullName = $row['L'];
            $note = $row['F'];
            $bankAccount = $row['M'];

            try {
                if ($id = Participant::getIdFromVarSymbol($varSymbol))
                {
                    /** @var Participant $participant */
                    $participant = $this->participants->find($id);
                    if (!$participant)
                    {
                        throw new \RuntimeException('Nepodarilo se nalezt ucastnika #' . $id .'!');
                    }
                    if ($participant->isPaid())
                    {
                        throw new \RuntimeException('Ucastnik #' . $id .' uz byl oznacen jako zaplaceny drive!');
                    }
                    if ($participant->getPrice() !== $amount)
                    {
                        throw new \RuntimeException('U ucastnika #' . $id .' nesedi částka! ' . $participant->getPrice() . ' !== ' . $amount);
                    }

                    $participant->setPaid(true);

                    $this->em->flush();
                    $this->em->clear();
                }
                elseif ($id = Serviceteam::getIdFromVarSymbol($varSymbol))
                {
                    /** @var Serviceteam $serviceteam */
                    $serviceteam = $this->serviceteam->find($id);
                    if (!$serviceteam)
                    {
                        throw new \RuntimeException('Nepodarilo se nalezt servisaka #' . $id .'!');
                    }
                    if ($serviceteam->isPaid())
                    {
                        throw new \RuntimeException('Servisak #' . $id .' uz byl oznacen jako zaplaceny drive!');
                    }
                    if ($serviceteam->getPrice() !== $amount)
                    {
                        throw new \RuntimeException('U servisaka #' . $id .' nesedi částka! ' . $serviceteam->getPrice() . ' !== ' . $amount);
                    }

                    $serviceteam->setPaid(true);

                    $this->em->flush();
                    $this->em->clear();
                }
                else
                {
                    throw new \RuntimeException('Neznamy var.symbol');
                }

                $success[] = $varSymbol;
            }
            catch (\Exception $e)
            {
                $errors[] = sprintf('Platba s var.symbolem <strong>%s</strong> (%s) z uctu <strong>%s</strong>: %s', $varSymbol, $amount, $bankAccount, $e->getMessage());
            }

        }

        echo '<pre>';
        echo 'Uspesne naimportovano: ' . count($success) . ' plateb' . "\n\n\n";
        echo implode("\n", $errors);

        $this->terminate();
    }

}



