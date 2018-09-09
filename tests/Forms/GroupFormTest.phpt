<?php
/**
 * Test: AppTests\Forms\GroupFormTest.
 *
 * @testCase \GroupFormTestTest
 * @author Petr Sladek <petr.sladek@skaut.cz>
 * @package
 */

namespace AppTests\Forms;

use App\Model\Entity\Group;
use App\Forms\IGroupFormFactory;
use App\Module\Front\Presenters\LoginPresenter;
use App\Module\Front\Presenters\MapPresenter;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Tester;
use Tester\Assert;

$container = require_once __DIR__ . '/../bootstrap.php';


/**
 * @author Petr Sladek <petr.sladek@skaut.cz>
 */
class GroupFormTestTest extends Tester\TestCase
{

	private $container;

	/** @var EntityManager */
	private $em;

	/**
	 * @var Group
	 */
	private $group;


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
//		$schemaTool->dropSchema($metadata);
		$schemaTool->dropDatabase();
		$schemaTool->createSchema($metadata);

		$this->group = new Group();
		$this->group->name = "Testovací";
		$this->group->city = "Testov";
		$this->group->note = "Poznámka";

		$this->em->persist($this->group);
		$this->em->flush();
	}


	public function testControl()
	{
		/** @var IGroupFormFactory $factory */
		$factory = $this->container->getByType(IGroupFormFactory::class);
		$control = $factory->create($this->group->id);

		$values = $control['form']->getValues(true);
		$values['name'] = 'Nove jmeno';

		$control->onSave[] = function ($_, $group)
		{
			Assert::equal($group, $this->group);
			Assert::equal($group->name, 'Nove jmeno');
		};

		$control['form']->setValues($values);
		$control['form']->setSubmittedBy($control['form']['send']);
		$control['form']->fireEvents();

		Assert::true($control['form']->isSuccess());
	}


//    protected function tearDown()
//    {
//        // Smaze DB
//        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
//        $schemaTool = new SchemaTool($this->em);
//        $schemaTool->dropSchema($metadata);
//        $schemaTool->dropDatabase();
//    }
}


$test = new GroupFormTestTest($container);
$test->run();