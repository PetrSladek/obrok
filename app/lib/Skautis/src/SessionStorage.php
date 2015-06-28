<?php


namespace PetrSladek\SkautIS;
use Nette\Object;


/**
 * @property string $signal_response_link
 * @property string $last_request
 */
class SessionStorage extends Object
{

	/**
	 * @var \Nette\Http\SessionSection
	 */
	protected $session;



	/**
     * @param string $appId
	 * @param \Nette\Http\Session $session
	 */
	public function __construct(\SkautIS\SkautIS $skautIS, \Nette\Http\Session $session)
	{
		$this->session = $session->getSection(__CLASS__. "/" . $skautIS->getConfig()->getAppId());
	}


    /**
     * @param $key
     * @param $value
     * @return void
     */
	public function set($key, $value)
	{
		$this->session->$key = $value;
	}


	/**
	 * @param string $key The key of the data to retrieve
	 * @param mixed $default The default value to return if $key is not found
	 *
	 * @return mixed
	 */
	public function get($key, $default = FALSE)
	{
		return isset($this->session->$key) ? $this->session->$key : $default;
	}



	/**
	 * Clear the data with $key from the persistent storage
	 *
	 * @param string $key
	 * @return void
	 */
	public function clear($key)
	{
		unset($this->session->$key);
	}



	/**
	 * Clear all data from the persistent storage
	 *
	 * @return void
	 */
	public function clearAll()
	{
		$this->session->remove();
	}



	/**
	 * @param string $name
	 * @return mixed
	 */
	public function &__get($name)
	{
		$value = $this->get($name);
		return $value;
	}



	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}



	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->session->{$name});
	}



	/**
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->clear($name);
	}

}
