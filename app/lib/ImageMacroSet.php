<?php

namespace App\MacrosSet;

use App\Services\ImageService;
use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\InvalidStateException;

/**
 * @author Petr Sládek <petr.sladek@skaut.cz>
 */
class ImageMacroSet extends MacroSet
{

	/**
	 * @var bool
	 */
	private $isUsed = FALSE;



	/**
	 * @param Compiler $compiler
	 *
	 * @return ImageMacroSet|MacroSet
	 */
	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);

		/**
		 * {img [namespace/]$name[, $size[, $flags]]}
		 */
		$me->addMacro('img', array($me, 'macroImg'), NULL, array($me, 'macroAttrImg'));

		return $me;
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 * @throws CompileException
	 */
	public function macroImg(MacroNode $node, PhpWriter $writer)
	{
		$this->isUsed = TRUE;
//		$arguments = Helpers::prepareMacroArguments($node->args);
		$arguments = $node->args;
		if ($arguments["name"] === NULL) {
			throw new CompileException("Please provide filename.");
		}

		$arguments = array_map(function ($value) use ($writer) {
			return $value ? $writer->formatWord($value) : 'NULL';
		}, $arguments);

		$command = '$imageService->getImageUrl(' . implode(", ", $arguments) . ')';

		return $writer->write('echo %escape(' . $writer->formatWord($command) . ')');
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 * @throws CompileException
	 */
	public function macroAttrImg(MacroNode $node, PhpWriter $writer)
	{
		$this->isUsed = TRUE;
//		$arguments = Helpers::prepareMacroArguments($node->args);
		$arguments = $node->args;
		if ($arguments["name"] === NULL) {
			throw new CompileException("Please provide filename.");
		}

		$arguments = array_map(function ($value) use ($writer) {
			return $value ? $writer->formatWord($value) : 'NULL';
		}, $arguments);

		$command = '$imageService->getImageUrl(' . implode(", ", $arguments) . ')';

		return $writer->write('?> src="<?php echo %escape(' . $writer->formatWord($command) . ')?>" <?php');
	}



	/**
	 */
	public function initialize()
	{
		$this->isUsed = FALSE;
	}



	/**
	 * Finishes template parsing.
	 *
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		if (!$this->isUsed) {
			return array();
		}

		return array(
			get_called_class() . '::validateTemplateParams($template);',
			NULL
		);
	}



	/**
	 * @param Template $template
	 * @throws InvalidStateException
	 */
	public static function validateTemplateParams(Template $template)
	{
		$params = $template->getParameters();
		if (!isset($params['imageService']) || !$params['imageService'] instanceof ImageService) {
			$where = isset($params['control']) ?
				" of component " . get_class($params['control']) . '(' . $params['control']->getName() . ')'
				: NULL;

			throw new InvalidStateException(
				'Please provide an instanceof ImageService ' .
				'as a parameter $imageService to template' . $where
			);
		}
	}

}
