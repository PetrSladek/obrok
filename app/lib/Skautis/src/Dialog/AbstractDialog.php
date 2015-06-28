<?php


namespace PetrSladek\SkautIS\Dialog;

use PetrSladek\SkautIS\SessionStorage;
use PetrSladek\SkautIS\SkautIS;
use Nette\Application;
use Nette\Application\Responses;
use Nette\Application\UI\PresenterComponent;
use Nette\Http\UrlScript;
use Nette;



/**
 * @author Petr Sladek <petr.sladek@skaut.cz>
 *
 * @method onResponse(AbstractDialog $dialog)
 */
abstract class AbstractDialog extends PresenterComponent
{

    /**
     * @var array of function(AbstractDialog $dialog)
     */
    public $onResponse = array();

    /**
     * @var SkautIS
     */
    protected $skautis;

    /**
     * @var SessionStorage
     */
    protected $session;

    /**
     * @var Application\UI\Link|UrlScript
     */
    protected $returnUri;



    /**
     * @param SkautIS $skautis
     */
    public function __construct(SkautIS $skautis)
    {
        $this->skautis = $skautis;
        $this->session = $skautis->getSession();

        parent::__construct();
    }



    /**
     * @return SkautIS
     */
    public function getSkautIS()
    {
        return $this->skautis;
    }





    /**
     * @return UrlScript
     */
    abstract public function getUrl();



    /**
     * @throws Application\AbortException
     */
    public function open()
    {
        $this->session->last_request = $this->getPresenter()->storeRequest();
        $this->session->signal_response_link = $this->link("response!");

        $this->presenter->redirectUrl($this->getUrl());
    }



    /**
     * Opens the dialog.
     */
    public function handleOpen()
    {
        $this->open();
    }



    /**
     * Signal called after redirect from SkautIS login page
     * It automatically calls the onResponse event.
     *
     * You don't have to redirect, the request before the auth process will be restored automatically.
     */
    public function handleResponse()
    {

        unset($this->session->signal_response_link);
        unset($this->session->last_request);

        $this->onResponse($this);


//        if (!empty($this->session->signal_response_link)) {
//            unset($this->session->signal_response_link);
//        }
//
//        if (!empty($this->session->last_request)) {
//            $presenter = $this->getPresenter();
//
//            try {
//                $presenter->restoreRequest($this->session->last_request);
//
//            } catch (Application\AbortException $e) {
//                $refl = new \ReflectionProperty('Nette\Application\UI\Presenter', 'response');
//                $refl->setAccessible(TRUE);
//
//                $response = $refl->getValue($presenter);
//                if ($response instanceof Responses\ForwardResponse) {
//                    $forwardRequest = $response->getRequest();
//
//                    $params = $forwardRequest->getParameters();
//                    unset($params['do']); // remove the signal to the google component
//                    $forwardRequest->setParameters($params);
//                }
//
//                $presenter->sendResponse($response);
//            }
//        }

        $this->presenter->redirect('this', array('state' => NULL, 'code' => NULL));
    }

}
