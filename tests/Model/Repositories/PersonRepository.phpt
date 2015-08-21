<?php
/**
 * Test: App\Model\Repositories\PersonRepository.
 *
 * @testCase App\PersonRepositoryTest
 * @author Petr Sladek <petr.sladek@skaut.cz>
 * @package App\Model\Repositories\PersonRepository
 */
namespace AppTests\App;

use Nette;
use Tester;
use Tester\Assert;
require_once __DIR__ . '/../../bootstrap.php';

/**
 * @author Petr Sladek <petr.sladek@skaut.cz>
 */
class PersonRepositoryTest extends Tester\TestCase
{
    public function setUp()
    {

    }
    public function testOne()
    {
        Assert::same("test","test");
        Assert::same("test","test2");
    }

}


\run(new PersonRepositoryTest());
