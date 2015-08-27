<?php
/**
* Test: App\Entity\Group\Group .
*
* @testCase App\Entity\Group\GroupTest
* @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
* @package App\Entity\Group
*/

namespace AppTests\Model\Entity\Group;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';

/**
* @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
*/
class GroupTest extends Tester\TestCase
{
    /** @var Group */
    protected $group;

    public function setUp()
    {

        $this->group = new Group();

        $this->group->setName("RK Murlok");
        $this->group->setCity("Blansko");

    }

    public function testAccess()
    {
        Assert::same("RK Murlok", $this->group->getName());
        Assert::same("Blansko", $this->group->getCity());

        Assert::same("RK Murlok", $this->group->name);
        Assert::same("Blansko", $this->group->city);

    }

    public function testAssociation() {

        $participant1 = new Participant();
        $participant1->setFirstName('Franta');
        $participant1->setLastName('Voprsalek');
        $this->group->addParticipant($participant1);
        Assert::equal(1, $this->group->getConfirmedParticipantsCount());

        $participant2 = new Participant();
        $participant2->setFirstName('Pepa');
        $participant2->setLastName('Zdepa');
        $this->group->addParticipant($participant1);
        Assert::equal(2, count($this->group->getConfirmedParticipants()));

        $participant3 = new Participant();
        $participant3->setFirstName('Jan');
        $participant3->setLastName('Novak');
        $this->group->addParticipant($participant3);
        Assert::equal(3, $this->group->getConfirmedParticipantsCount());

        $participant4 = new Participant();
        $participant4->setFirstName('Jon');
        $participant4->setLastName('Snow');
        $participant4->setGroup( new Group() );
        $participant4->getGroup()->setName('RK Muhehe');
        $participant4->getGroup()->setCity('Brno');


        Assert::exception(function() use($participant4) {
            $this->group->setBoss($participant4);
        }, '\InvalidArgumentException');

        Assert::equal(3, count($this->group->getConfirmedParticipants()));





    }
}

$test = new GroupTest($container);
$test->run();