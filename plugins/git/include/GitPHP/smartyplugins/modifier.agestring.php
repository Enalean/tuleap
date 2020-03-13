<?php
/**
 * agestring
 *
 * Smarty modifier to turn an age in seconds into a
 * human-readable string
 *
 */

/**
 * agestring smarty modifier
 *
 * @param int $age age in seconds
 * @return string human readable string
 */
function smarty_modifier_agestring($age)
{
    if ($age > 60 * 60 * 24 * 365 * 2) {
        $years = (int) ($age / 60 / 60 / 24 / 365);
        return sprintf(dngettext("gitphp", '%1$d year ago', '%1$d years ago', $years), $years);
    } elseif ($age > 60 * 60 * 24 * (365 / 12) * 2) {
        $months = (int) ($age / 60 / 60 / 24 / (365 / 12));
        return sprintf(dngettext("gitphp", '%1$d month ago', '%1$d months ago', $months), $months);
    } elseif ($age > 60 * 60 * 24 * 7 * 2) {
        $weeks = (int) ($age / 60 / 60 / 24 / 7);
        return sprintf(dngettext("gitphp", '%1$d week ago', '%1$d weeks ago', $weeks), $weeks);
    } elseif ($age > 60 * 60 * 24 * 2) {
        $days = (int) ($age / 60 / 60 / 24);
        return sprintf(dngettext("gitphp", '%1$d day ago', '%1$d days ago', $days), $days);
    } elseif ($age > 60 * 60 * 2) {
        $hours = (int) ($age / 60 / 60);
        return sprintf(dngettext("gitphp", '%1$d hour ago', '%1$d hours ago', $hours), $hours);
    } elseif ($age > 60 * 2) {
        $min = (int) ($age / 60);
        return sprintf(dngettext("gitphp", '%1$d min ago', '%1$d min ago', $min), $min);
    } elseif ($age > 2) {
        $sec = (int) $age;
        return sprintf(dngettext("gitphp", '%1$d sec ago', '%1$d sec ago', $sec), $sec);
    }

    return dgettext("gitphp", 'right now');
}
