<?php
/**
 * Skript obsahuje třídu, která slouží jako služba pro vytváření mapperů.
 *
 * @author Michal Kočárek <michal.kocarek@brainbox.cz>
 * @since  2013.03.01
 */

namespace App\Services;

use Nette\Application\UI\ITemplate;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\SmartObject;
use Nette\Templating\FileTemplate;
use Tracy\Debugger;

/**
 * Instance této třídy je továrnou pro vytváření emailů.
 */
class EmailsService
{
	use SmartObject;

	/**
	 * @var ITemplate
	 */
	private $_template;

	/**
	 * @var IMailer
	 */
	private $_mailer;

	/**
	 * @var String
	 */
	private $_templateDir;

	/**
	 * @var String
	 */
	private $_defaultFrom;

	/**
	 * @var String Formát předmětu emailů (% .. zadaný předmět)
	 */
	private $_subjectFormat = '%';


	/**
	 * @param IMailer $mailer
	 */
	public function __construct(IMailer $mailer, $templateDir)
	{
		$this->_mailer = $mailer;
		$this->_templateDir = $templateDir;
	}


	public function setTemplate(ITemplate $template)
	{
		$this->_template = $template;
	}


	public function setDefaultFrom($from)
	{
		$this->_defaultFrom = $from;
	}


	public function setSubjectFormat($subjectFormat)
	{
		$this->_subjectFormat = $subjectFormat;
	}


	/**
	 * @return IMailer
	 */
	public function getMailer()
	{
		return $this->_mailer;
	}


	public function createTemplate($templateName)
	{
		$tplFile = $this->_templateDir . 'email_' . $templateName . '.latte';

		$template = clone $this->_template;
		$template->setFile($tplFile);

		return $template;
	}


	/**
	 * @param string  $template Jméno šablony
	 * @param string $subject   Předmět
	 * @param mixed[] $data     Data do šablony
	 *
	 * @return Message
	 */
	public function create($templateName, $subject, $data = array(), $control = null)
	{

		$forrmatedSubject = str_replace('%', $subject, $this->_subjectFormat);

		// Priprava sablony
		/** @var FileTemplate $template */
		$template = $this->createTemplate($templateName);

		$template->subject = $subject;
		$template->forrmatedSubject = $forrmatedSubject;
		if ($control)
		{
			$template->_control = $template->_presenter = $control;
		}

		foreach ($data as $key => $val)
		{
			$template->$key = $val;
		}

		// Pripdava emailu
		$message = new Message();
		if ($this->_defaultFrom)
		{
			$message->setFrom($this->_defaultFrom);
		}
		$message->setSubject($forrmatedSubject);
		$message->setHtmlBody($template, $this->_templateDir);

		return $message;
	}


	/**
	 * @param Message $message
	 */
	public function send(Message $message)
	{
		try
		{
//			$this->getMailer()->send($message);
		}
		catch (\Exception $e)
		{
			Debugger::log($e, 'emails');
		}

	}

}
