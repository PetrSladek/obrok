<?php
/**
 * Test: App\Model\Repositories\PersonRepository.
 *
 * @testCase App\PersonRepositoryTest
 * @author   Petr Sladek <petr.sladek@skaut.cz>
 * @package  App\Model\Repositories\PersonRepository
 */
namespace AppTests\Model\Repositories;

use App\Model\Address;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\PersonsRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette;
use Tester;
use Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';


/**
 * @author Petr Sladek <petr.sladek@skaut.cz>
 */
class PersonRepositoryTest extends Tester\TestCase
{
	private $container;

	/** @var EntityManager */
	private $em;


	/**
	 * PersonRepositoryTest constructor.
	 *
	 * @param $container
	 */
	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->em = $this->container->getByType(EntityManager::class);
	}


	public function setUp()
	{

		Tester\Environment::lock('db', dirname(TEMP_DIR));

		// Smaye db a vytvori Vytvori cistou DB
		$metadata = $this->em->getMetadataFactory()->getAllMetadata();
		$schemaTool = new SchemaTool($this->em);
//        $schemaTool->dropSchema($metadata);
		$schemaTool->dropDatabase();
		$schemaTool->createSchema($metadata);

	}


	public function testCastPerson()
	{

		$person1 = new UnspecifiedPerson();
		$person1->setFullName('Host', 'Hostovic', 'Hostov');
		$person1->setPhone('+420 123 456');
		$person1->setAddress(new Address("Tučníkova 11", "Tučňákov", "123 45"));
		$this->em->persist($person1);

		$person2 = new Serviceteam();
		$person2->setFullName('Servisak', 'Servisákovič', 'Servisákov');
		$person2->setPhone('+420 123 456');
		$person2->setAddress(new Address("Ulice 14", "Mesto", "666 99"));
		$this->em->persist($person2);

		$person3 = new Participant();
		$person3->setFullName('Ucastnik', 'Ucastnikovic', 'Ucastnikov');
		$person3->setPhone('+420 123 456');
		$person3->setAddress(new Address("Ulice 14", "Mesto", "666 99"));
		$this->em->persist($person3);

		$this->em->flush();

		/** @var PersonsRepository $repo */
		$repo = $this->em->getRepository(Person::class);

		$all = $repo->findAll();

		// musi tam byt vsichni tri
		Assert::same(3, count($all));
		$types = [];
		foreach ($all as $person)
		{
			$types[] = get_class($person);
		}

		Assert::contains(UnspecifiedPerson::class, $types);
		Assert::contains(Serviceteam::class, $types);
		Assert::contains(Participant::class, $types);

//        $person1->type = Person::TYPE_PARTICIPANT;
//        $this->em->flush();

		$repo->changePersonTypeTo($person1, Person::TYPE_PARTICIPANT);
		Assert::type(Participant::class, $person1);

//        $repo->changePersonTypeTo($person2, Person::TYPE_PARTICIPANT);
//        Assert::type(Participant::class, $person);

	}

//    /***/
//    public function testGuestCastToParticipant()
//    {
//        Assert::true(true);
//    }

//    protected function tearDown()
//    {
//        // Smaze DB
//        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
//        $schemaTool = new SchemaTool($this->em);
//        $schemaTool->dropSchema($metadata);
//        $schemaTool->dropDatabase();
//    }

}


$test = new PersonRepositoryTest($container);
$test->run();