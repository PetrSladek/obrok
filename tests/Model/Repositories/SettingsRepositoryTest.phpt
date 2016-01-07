<?php
/**
 * Test: App\Model\Repositories\SettingsRepository.
 *
 * @testCase App\PersonRepositoryTest
 * @author   Petr Sladek <petr.sladek@skaut.cz>
 * @package  App\Model\Repositories\SettingsRepository
 */
namespace AppTests\Model\Repositories;

use App\Model\Address;
use App\Model\Entity\Setting;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\SettingsRepository;
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
class SettingsRepositoryTest extends Tester\TestCase
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

		// Smaze db a vytvori Vytvori cistou DB
		$metadata = $this->em->getMetadataFactory()->getAllMetadata();
		$schemaTool = new SchemaTool($this->em);
//        $schemaTool->dropSchema($metadata);
		$schemaTool->dropDatabase();
		$schemaTool->createSchema($metadata);

	}


	public function testSaveBoolSetting()
	{
		$value = true;

		/** @var SettingsRepository $repo */
		$repo = $this->em->getRepository(Setting::class);
		$repo->set('key1', $value);

		Assert::equal($value, (bool) $repo->get('key1'));
	}

}


\run(new SettingsRepositoryTest($container));
