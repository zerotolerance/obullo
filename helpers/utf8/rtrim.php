<?php defined('BASE') or die('No direct script access.');
/**
 * UTF8::rtrim
 *
 * @package    Obullo
 * @author     Kohana Team
 * @copyright  (c) 2007-2011 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function utf8_rtrim($str, $charlist = NULL)
{
	if ($charlist === NULL)
		return rtrim($str);

	if (UTF8::is_ascii($charlist))
		return rtrim($str, $charlist);

	$charlist = preg_replace('#[-\[\]:\\\\^/]#', '\\\\$0', $charlist);

	return preg_replace('/['.$charlist.']++$/uD', '', $str);
}