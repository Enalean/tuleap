<?php
/**
 * Buglink
 *
 * Modifier to turn bug references into links
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 */

/**
 * buglink smarty modifier
 *
 * @param string $text text to find bug references in
 * @param string $pattern search pattern
 * @param string $link link pattern
 * @return string text with bug references linked
 */
function smarty_modifier_buglink($text, $pattern = null, $link = null)
{
	if (empty($text) || empty($pattern) || empty($link))
		return $text;

	$fullLink = '<a href="' . $link . '">${0}</a>';

	return preg_replace($pattern, $fullLink, $text);
}
