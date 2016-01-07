<?php
/**
 * Test: App\Model\Entity\ServiceteamTest entity.
 *
 * @testCase App\Model/Entity\ServiceteamTest
 * @author   Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 * @package  App\Model/Entity
 */

namespace AppTests\Model\Entity\Serviceteam;

use App\Model\Entity\Job;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use App\Model\Entity\Workgroup;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';


/**
 * @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 */
class ServiceteamTest extends Tester\TestCase
{

	protected $serviceteam;


	public function setUp()
	{

		$this->serviceteam = new Serviceteam();

		$this->serviceteam->setFirstName("Petr");
		$this->serviceteam->setLastName("Sládek");
		$this->serviceteam->setNickName("Peggy");

	}


	public function testAccess()
	{
		Assert::same("Petr", $this->serviceteam->getFirstName());
		Assert::same("Sládek", $this->serviceteam->getLastName());
		Assert::same("Peggy", $this->serviceteam->getNickName());

		Assert::same("Petr", $this->serviceteam->firstName);
		Assert::same("Sládek", $this->serviceteam->lastName);
		Assert::same("Peggy", $this->serviceteam->nickName);

		Assert::same("Petr Sládek (Peggy)", $this->serviceteam->getFullname());
	}


	public function testAssociation()
	{

		$team = new Team();
		$team->setAbbr('REG');
		$team->setName('Registrace a IT');
		$this->serviceteam->setTeam($team);

		$workgroup = new Workgroup();
		$workgroup->setName('Registrační systém');
		$this->serviceteam->setWorkgroup($workgroup);

		$job = new Job();
		$job->setName('Master of code');
		$this->serviceteam->setJob($job);

		Assert::same($team, $this->serviceteam->team);
		Assert::same("REG", $this->serviceteam->team->getAbbr());
		Assert::same("Registrace a IT", $this->serviceteam->team->getName());

		Assert::same($workgroup, $this->serviceteam->workgroup);
		Assert::same("Registrační systém", $this->serviceteam->workgroup->name);
		Assert::same("Registrační systém", $this->serviceteam->workgroup->getName());

		Assert::same($job, $this->serviceteam->job);
		Assert::same("Master of code", $this->serviceteam->job->name);
		Assert::same("Master of code", $this->serviceteam->job->getName());

	}
}


$test = new ServiceteamTest();
$test->run();