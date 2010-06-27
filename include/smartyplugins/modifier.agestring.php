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
	if ($age > 60*60*24*365*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d years ago'), (int)($age/60/60/24/365));
	else if ($age > 60*60*24*(365/12)*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d months ago'), (int)($age/60/60/24/(365/12)));
	else if ($age > 60*60*24*7*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d weeks ago'), (int)($age/60/60/24/7));
	else if ($age > 60*60*24*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d days ago'), (int)($age/60/60/24));
	else if ($age > 60*60*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d hours ago'), (int)($age/60/60));
	else if ($age > 60*2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d min ago'), (int)($age/60));
	else if ($age > 2)
		return sprintf(GitPHP_Resource::GetInstance()->GetResource('%1$d sec ago'), (int)$age);
	return GitPHP_Resource::GetInstance()->GetResource('right now');
}

?>
