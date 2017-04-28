<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;

/**
 * Class RouterFactory
 * @package App
 * @author  psl <petr.sladek@webnode.com>
 */
class RouterFactory
{
    /**
     * Jsme na https?
     *
     * @return bool
     */
    private static function isSecure() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || @$_SERVER['SERVER_PORT'] == 443;
    }

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
        if (self::isSecure())
        {
            Route::$defaultFlags |= Route::SECURED;
        }

		$router = new RouteList();
		$router[] = new Route('index.php', 'Front:Login:default', Route::ONE_WAY);

		$router[] = $databaseRouter = new RouteList('Database');
		$databaseRouter[] = new Route('database/<presenter>/<action>[/<id>]', 'Dashboard:default');

		$router[] = $frontParticipantsRouter = new RouteList('Front:Participants');

        $frontParticipantsRouter[] = new Route('pozvanka/<hash>-<id>', 'Invitation:toGroup');
        $frontParticipantsRouter[] = new Route('pozvanka/registrace/<hash>-<id>', 'Registration:toGroup');

		$frontParticipantsRouter[] = new Route('ucastnici/<presenter>/<action>[/<id>]', [
			'presenter' => array(
				Route::VALUE        => 'Homepage',
				Route::FILTER_TABLE => array(
					'registrace' => 'Registration',
					'nastenka'   => 'Homepage',
				),
			),
			'action'    => 'default',
			'id'        => null,
		]);

		$router[] = $frontServiceteamRouter = new RouteList('Front:Serviceteam');
		$frontServiceteamRouter[] = new Route('servistym/<presenter>/<action>[/<id>]', [
			'presenter' => array(
				Route::VALUE        => 'Homepage',
				Route::FILTER_TABLE => array(
					'registrace' => 'Registration',
					'nastenka'   => 'Homepage',
				),
			),
			'action'    => 'default',
			'id'        => null,
		]);

		$router[] = $frontRouter = new RouteList('Front');
		$frontRouter[] = new Route('<presenter>/<action>[/<id>]', 'Login:default');

		return $router;
	}

}
