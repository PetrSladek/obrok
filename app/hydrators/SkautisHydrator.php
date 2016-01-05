<?php
namespace App\Hydrators;

use App\Model\Entity\Person;
use Nette\Utils\DateTime;
use Skautis\Skautis;

class SkautisHydrator
{

	/**
	 * @var Skautis
	 */
	protected $skautis;


	/**
	 * SkautisHydrator constructor.
	 *
	 * @param Skautis $skautis
	 */
	public function __construct(Skautis $skautis)
	{
		$this->skautis = $skautis;
	}


	/**
	 * @param Person $person
	 * @param        $skautisPersonID
	 * @param bool   $onlyEmptyProperties
	 */
	public function hydrate(Person $person, $skautisPersonID, $onlyEmptyProperties = false)
	{

		$personDetail = $this->skautis->org->personDetail(["ID" => $skautisPersonID]);
		$personContactAll = $this->skautis->org->PersonContactAll(["ID_Person" => $skautisPersonID, "ID_ContactType" => "telefon_hlavni"]);
		$personOtherDetail = $this->skautis->org->PersonOtherDetail(array("ID" => $skautisPersonID));

		// prepsise uz i vyplnene property
		$force = !$onlyEmptyProperties;

		if ($force || $person->firstName === null)
		{
			$person->firstName = $personDetail->FirstName ? : null;
		}
		if ($force || $person->lastName === null)
		{
			$person->lastName = $personDetail->LastName ? : null;
		}
		if ($force || $person->nickName === null)
		{
			$person->nickName = $personDetail->NickName ? : null;
		}
		if ($force || $person->addressStreet === null)
		{
			$person->addressStreet = $personDetail->Street ? : null;
		}
		if ($force || $person->addressCity === null)
		{
			$person->addressCity = $personDetail->City ? : null;
		}
		if ($force || $person->addressPostcodefirstName === null)
		{
			$person->addressPostcode = $personDetail->Postcode ? : null;
		}
		if ($force || $person->birthdate === null)
		{
			$person->birthdate = $personDetail->Birthday ? DateTime::from($personDetail->Birthday) : null;
		}
		if ($force || $person->email === null)
		{
			$person->email = $personDetail->Email ? : null;
		}
		if ($force || $person->gender === null)
		{
			$person->gender = !empty($personDetail->ID_Sex) ? ($personDetail->ID_Sex == 'male' ? Person::GENDER_MALE : Person::GENDER_FEMALE) : null;
		}
		if ($force || $person->phone === null)
		{
			$person->phone = count((array) $personContactAll) && !empty($personContactAll[0]->Value) ? $personContactAll[0]->Value : null;
		}
//        $person->phoneIsSts = count((array)$personContactAll) && !empty($personContactAll[0]->IsSts) ? (bool) $personContactAll[0]->IsSts : null;

		$alergy = !empty($personOtherDetail->Allergy) ? $personOtherDetail->Allergy : null; // alergie
		$drugs = !empty($personOtherDetail->Drugs) ? $personOtherDetail->Drugs : null; // léky
		$healthLimitation = !empty($personOtherDetail->HealthLimitation) ? $personOtherDetail->HealthLimitation : null; // zdravotni omezeni
		$note = !empty($personOtherDetail->Note) ? $personOtherDetail->Note : null; // zdravotni omezeni

		if ($force || $person->health === null)
		{
			$person->health =
				($alergy ? "Alergie: $alergy\n" : '') .
				($drugs ? "Léky: $drugs\n" : '') .
				($healthLimitation ? "$healthLimitation\n" : '');
		}

		if ($force || $person->noteInternal === null)
		{
			$person->noteInternal = ($note ? "Poznámka ze SkautISu: $note\n" : '');
		}

		$person->skautisPersonId = $personDetail->ID;
		$person->skautisUserId = $personDetail->ID_User;
	}

}