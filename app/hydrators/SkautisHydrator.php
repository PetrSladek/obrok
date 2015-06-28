<?php
namespace App\Hydrators;


use App\Model\Entity\Person;
use Nette\Utils\DateTime;
use Skautis\Skautis;

class SkautisHydrator {


    /**
     * @var Skautis
     */
    protected $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }


    public function hydrate(Person $person, $skautisPersonID) {

        $personDetail = $this->skautis->org->personDetail(["ID" => $skautisPersonID]);
        $personContactAll = $this->skautis->org->PersonContactAll(["ID_Person" => $skautisPersonID, "ID_ContactType"=> "telefon_hlavni"]);
        $personOtherDetail = $this->skautis->org->PersonOtherDetail(array("ID" => $skautisPersonID));

        $person->firstName = $personDetail->FirstName ?: null;
        $person->lastName = $personDetail->LastName ?: null;
        $person->nickName = $personDetail->NickName ?: null;
        $person->addressStreet = $personDetail->Street ?: null;
        $person->addressCity = $personDetail->City ?: null;
        $person->addressPostcode = $personDetail->Postcode ?: null;
        $person->birthdate = $personDetail->Birthday ? DateTime::from($personDetail->Birthday) : null;
        $person->email = $personDetail->Email ?: null;
        $person->gender = !empty($personDetail->ID_Sex) ? ($personDetail->ID_Sex == 'male' ? Person::GENDER_MALE : Person::GENDER_FEMALE) : null;

        $person->phone = count((array)$personContactAll) && !empty($personContactAll[0]->Value) ? $personContactAll[0]->Value : null;
//        $person->phoneIsSts = count((array)$personContactAll) && !empty($personContactAll[0]->IsSts) ? (bool) $personContactAll[0]->IsSts : null;

        $alergy = !empty($personOtherDetail->Allergy) ? $personOtherDetail->Allergy : null; // alergie
        $drugs = !empty($personOtherDetail->Drugs ) ? $personOtherDetail->Drugs  : null; // léky
        $healthLimitation = !empty($personOtherDetail->HealthLimitation) ? $personOtherDetail->HealthLimitation  : null; // zdravotni omezeni
        $note = !empty($personOtherDetail->Note) ? $personOtherDetail->Note  : null; // zdravotni omezeni

        $person->health =
            ($alergy ? "Alergie: $alergy\n" : '').
            ($drugs ? "Léky: $drugs\n" : '').
            ($healthLimitation ? "$healthLimitation\n" : '');

        $person->noteInternal = ($note ? "Poznámka ze SkautISu: $note\n" : '');

        $person->skautisPersonId = $personDetail->ID;
        $person->skautisUserId = $personDetail->ID_User;

    }


}