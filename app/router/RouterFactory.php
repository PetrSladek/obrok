<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;


class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
//		$router = new RouteList();
//		$router[] = new Route('<presenter>/<action>[/<id>]', 'Participants:Homepage:default');
//		return $router;

        $router = new RouteList();
        $router[] = new Route('index.php', 'Front:Default:default', Route::ONE_WAY);

        $router[] = $databaseRouter = new RouteList('Database');
        $databaseRouter[] = new Route('database/<presenter>/<action>', 'Dashboard:default');

        $router[] = $frontParticipantsRouter = new RouteList('Front:Participants');
        $frontParticipantsRouter[] = new Route('pozvanka/<hash>-<id>', 'Registration:toGroup');
        $frontParticipantsRouter[] = new Route('ucastnici/<presenter>/<action>[/<id>]',[
            'presenter' => array(
                Route::VALUE => 'Homepage',
                Route::FILTER_TABLE => array(
                    'registrace' => 'Registration',
                    'nastenka' => 'Homepage',
                ),
            ),
            'action' => 'default',
            'id' => NULL,
        ]);

        $router[] = $frontServiceteamRouter = new RouteList('Front:Serviceteam');
        $frontServiceteamRouter[] = new Route('servistym/<presenter>/<action>[/<id>]', [
            'presenter' => array(
                Route::VALUE => 'Homepage',
                Route::FILTER_TABLE => array(
                    'registrace' => 'Registration',
                    'nastenka' => 'Homepage',
                ),
            ),
            'action' => 'default',
            'id' => NULL,
        ]);

        $router[] = $frontRouter = new RouteList('Front');
        $frontRouter[] = new Route('<presenter>/<action>[/<id>]', 'Login:default');



        return $router;
	}

}
