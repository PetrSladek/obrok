<?php

namespace PetrSladek\SkautIS;

use Nette\Application;
use Nette\Http\ISessionStorage;
use Nette\Http\Request;
use Nette\Object;
use Nette\Security\User;

class SkautIS extends Object
{

	/**
	 * @var \Nette\Application\Application
	 */
	private $app;

	/**
	 * @var Request
	 */
	protected $httpRequest;

	/**
	 * @var SessionStorage
	 */
	private $session;

	/**
	 * @var \SkautIS\SkautIS
	 */
	private $client;

	/**
	 * The User_ID of SkautIS logged user.
	 * @var integer|null
	 */
	protected $userId;

    /**
     * The Person_ID of SkautIS logged user.
     * @var integer
     */
    protected $personId;


	public function __construct(Application\Application $app, User $user, Request $httpRequest, SessionStorage $session, \SkautIS\SkautIS $client)
	{
		$this->app = $app;
		$this->httpRequest = $httpRequest;
		$this->session = $session;
		$this->client = $client;

        if($user->isLoggedIn() && $this->isLoggedIn())
            $this->getClient()->getUser()->updateLogoutTime();

        // Po odhlaseni nette uzivatele odhlasit i skautis
        $user->onLoggedOut[] = function() {
            $this->destroySession();
            $this->getClient()->getUser()->resetLoginData();
        };

		$this->tryProcessResponse(); // ToDo nastavit v extensne do AfterCopile - pak to bude mozna fungovat i v presenterech ktery nemaji tuhle tridu injectlou
	}

    protected function tryProcessResponse() {

        // vytahnu data z response
        $token = $this->httpRequest->getPost('skautIS_Token');

        if($token) { // Pokud prisel v HTTP POSTu token
            // Nastavim ho
            $this->client->setLoginData($this->httpRequest->getPost());

            // A po nacteni aplikace zajistim presmerovani na signal response! komponenty, ktera login dialog otevřela
            $this->app->onPresenter[] = function(Application\Application $sender, Application\UI\Presenter $presenter) {

                $presenter->onShutdown[] = function(Application\UI\Presenter $presenter) {

                    if(!empty($this->session->signal_response_link)) {
                        // Vnutím presenteru přesměrování na jinou URL
                        $refl = new \ReflectionProperty('Nette\Application\UI\Presenter', 'response');
                        $refl->setAccessible(TRUE);

//                        $response = new Application\Responses\TextResponse("My text response");
                        $response = new Application\Responses\RedirectResponse($this->session->signal_response_link);

                        $refl->setValue($presenter, $response);
                    }

                };
            };

        }

    }



	/**
	 * @return \SkautIS\SkautIS
	 */
	public function getClient()
	{
		return $this->client;
	}


	/**
	 * @return SessionStorage
	 */
	public function getSession()
	{
		return $this->session;
	}



    public function isLoggedIn() {
        return $this->client->getUser()->isLoggedIn();
    }


    /**
     * @return null|\stdClass
     */
    public function getUserData() {
        if(!$this->isLoggedIn())
            return null;

        return $this->client->usr->UserDetail();
    }



    /**
     * @return null|\stdClass
     */
    public function getPersonData()
    {
        if(!$this->isLoggedIn())
            return null;

        return $this->client->org->personDetail(array("ID" => $this->getPersonId()));
    }



    /**
     * Get the User_ID of SkautIS logged user.
     * @return string|null
     */
    public function getUserId()
    {
        if(!$this->isLoggedIn())
            return null;

        if ($this->userId === NULL) {
            $this->userId = $this->getUserData()->ID;
        }

        return $this->userId;
    }

    /**
     * Get the Person_ID of SkautIS logged user.
     *
     * @return string the UID if available.
     */
    public function getPersonId()
    {
        if(!$this->isLoggedIn())
            return null;

        if ($this->personId === NULL) {
            $this->personId = $this->getUserData()->ID_Person;
        }

        return $this->personId;
    }


    /**
	 * Destroy the current session
	 */
	public function destroySession()
	{
		$this->userId = NULL;
        $this->personId = NULL;
		$this->session->clearAll();
	}


	/**
     * Factory to create Login Dialog
	 * @return Dialog\LoginDialog
	 */
	public function createLoginDialog()
	{
		return new Dialog\LoginDialog($this);
	}

}
