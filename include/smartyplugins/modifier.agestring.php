<?php
/*
 *  modifier.agestring.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - convert age to a readable string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function smarty_modifier_agestring($age)
{
	$resource = GitPHP_Resource::GetInstance();

	if ($age > 60*60*24*365*2) {

		$years = (int)($age/60/60/24/365);
		return sprintf($resource->ngettext('%1$d year ago', '%1$d years ago', $years), $years);

	} else if ($age > 60*60*24*(365/12)*2) {

		$months = (int)($age/60/60/24/(365/12));
		return sprintf($resource->ngettext('%1$d month ago', '%1$d months ago', $months), $months);

	} else if ($age > 60*60*24*7*2) {

		$weeks = (int)($age/60/60/24/7);
		return sprintf($resource->ngettext('%1$d week ago', '%1$d weeks ago', $weeks), $weeks);

	} else if ($age > 60*60*24*2) {

		$days = (int)($age/60/60/24);
		return sprintf($resource->ngettext('%1$d day ago', '%1$d days ago', $days), $days);

	} else if ($age > 60*60*2) {

		$hours = (int)($age/60/60);
		return sprintf($resource->ngettext('%1$d hour ago', '%1$d hours ago', $hours), $hours);

	} else if ($age > 60*2) {

		$min = (int)($age/60);
		return sprintf($resource->ngettext('%1$d min ago', '%1$d min ago', $min), $min);

	} else if ($age > 2) {

		$sec = (int)$age;
		return sprintf($resource->ngettext('%1$d sec ago', '%1$d sec ago', $sec), $sec);

	}

	return $resource->translate('right now');
}

?>
