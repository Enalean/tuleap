<?php
/**
 * Buglink
 *
 * Modifier to turn bug references into links
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

function smarty_modifier_buglink($text, $pattern = null, $link = null)
{
	if (empty($text) || empty($pattern) || empty($link))
		return $text;

	$fullLink = '<a href="' . $link . '">${0}</a>';

	return preg_replace($pattern, $fullLink, $text);
}
