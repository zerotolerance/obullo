<?php defined('BASE') or die('No direct script access.');
/**
 * UTF8::stristr
 *
 * @package    Obullo
 * @author     Kohana Team
 * @copyright  (c) 2007-2011 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function utf8_stristr($str, $search)
{
	if (UTF8::is_ascii($str) AND UTF8::is_ascii($search))
		return stristr($str, $search);

	if ($search == '')
		return $str;

	$str_lower = UTF8::strtolower($str);
	$search_lower = UTF8::strtolower($search);

	preg_match('/^(.*?)'.preg_quote($search_lower, '/').'/s', $str_lower, $matches);

	if (isset($matches[1]))
		return substr($str, strlen($matches[1]));

	return FALSE;
}