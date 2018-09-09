<?php
/**
 * Test: App\Model\Entity\Setting.
 *
 * @testCase App\Entity\Setting\SettingTest
 * @author   Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 * @package  App\Entity\Group
 */

namespace AppTests\Model\Entity\Group;

use App\Model\Entity\Group;
use App\Model\Entity\Setting;
use Kdyby;
use Tester;
use Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';


/**
 * @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 */
class SettingTest extends Tester\TestCase
{
	/** @var Group */
	protected $group;


	public function setUp()
	{

	}


	public function testValues()
	{
		$setting = new Setting('bool', true);
		Assert::true($setting->value);
	}

}


$test = new SettingTest($container);
$test->run();