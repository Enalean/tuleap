<?php
/*
 *  util.age_string.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - convert age to a readable string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

include('i18n.lookupstring.php');

function age_string($age)
{
	if ($age > 60*60*24*365*2) {
		return sprintf(lookupstring('ageyearsago'), (int)($age/60/60/24/365));
		//i18n: return sprintf(lookupstring('%1$d years ago'), (int)($age/60/60/24/365));
	} else if ($age > 60*60*24*(365/12)*2) {
		return sprintf(lookupstring('agemonthsago'), (int)($age/60/60/24/(365/12)));
		//i18n: return sprintf(lookupstring('%1$d months ago'), (int)($age/60/60/24/(365/12)));
	} else if ($age > 60*60*24*7*2) {
		return sprintf(lookupstring('ageweeksago'), (int)($age/60/60/24/7));
		//i18n: return sprintf(lookupstring('%1$d weeks ago'), (int)($age/60/60/24/7));
	} else if ($age > 60*60*24*2) {
		return sprintf(lookupstring('agedaysago'), (int)($age/60/60/24));
		//i18n: return sprintf(lookupstring('%1$d days ago'), (int)($age/60/60/24));
	} else if ($age > 60*60*2) {
		return sprintf(lookupstring('agehoursago'), (int)($age/60/60));
		//i18n: return sprintf(lookupstring('%1$d hours ago'), (int)($age/60/60));
	} else if ($age > 60*2) {
		return sprintf(lookupstring('ageminago'), (int)($age/60));
		//i18n: return sprintf(lookupstring('%1$d min ago'), (int)($age/60));
	} else if ($age > 2) {
		return sprintf(lookupstring('agesecago'), (int)$age);
		//i18n: return sprintf(lookupstring('%1$d sec ago'), (int)$age);
	}
	return lookupstring('agerightnow');
	//i18n: return lookupstring("right now");
}

?>
