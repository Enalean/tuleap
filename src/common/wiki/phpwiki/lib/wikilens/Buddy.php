<?php
//-*-php-*-
rcs_id('$Id: Buddy.php,v 1.3 2004/11/21 11:59:26 rurban Exp $');

// It is anticipated that when userid support is added to phpwiki,
// this object will hold much more information (e-mail,
// home(wiki)page, etc.) about the user.

// There seems to be no clean way to "log out" a user when using HTTP
// authentication. So we'll hack around this by storing the currently
// logged in username and other state information in a cookie.

// 2002-09-08 11:44:04 rurban
// Todo: Fix prefs cookie/session handling:
//       _userid and _homepage cookie/session vars still hold the
//       serialized string.
//       If no homepage, fallback to prefs in cookie as in 1.3.3.


require_once(dirname(__FILE__) . "/Utils.php");

/*
class Buddy extends WikiUserNew {}
*/

function addBuddy($user, $buddy, $dbi)
{
    $START_DELIM = _("Buddies:");
    // the delimiter is really a comma, but include a space to make it look
    // nicer (getBuddies strips out extra spaces when extracting buddies)
    $DELIM = ", ";

    addPageTextData($user, $dbi, $buddy, $START_DELIM, $DELIM);
}

function getBuddies($fromUser, $dbi, $thePage = "")
{
    $START_DELIM = $thePage . _("Buddies:");

    $buddies_array = getPageTextData($fromUser, $dbi, $START_DELIM);
    if (count($buddies_array) == 0 and $thePage !== "") {
        $buddies_array = getPageTextData($fromUser, $dbi, _("Buddies:"));
    }
    return $buddies_array;
}

function CoAgreement($dbi, $page, $users, $active_userid)
{
    //Returns a "yes" 1, "no" -1, or "unsure" 0 for whether
    //the group agrees on the page based on their ratings
    $cur_page = $page;

    $my_ratings_iter = $dbi->get_rating(0, $active_userid, $page);
    $my_ratings_single = $my_ratings_iter->next();
    $cur_rating = $my_ratings_single['ratingvalue'];

    $MIDDLE_RATING = 3;

    if ($cur_rating >= $MIDDLE_RATING) {
        $agreePos = 1;
    } else {
        $agreePos = 0;
    }
    foreach ($users as $buddy) {
        $buddy_rating_iter = $dbi->get_rating(0, $buddy, $cur_page);
        $buddy_rating_array = $buddy_rating_iter->next();
        $buddy_rating = $buddy_rating_array['ratingvalue'];
        if ($buddy_rating == "") {
            $agree = 1;
        } elseif ($agreePos && $buddy_rating >= $MIDDLE_RATING) {
            $agree = 1;
        } elseif (!$agreePos && $buddy_rating < $MIDDLE_RATING) {
            $agree = 1;
        } else {
            $agree = 0;
            break;
        }
    }
    if ($agree && $agreePos) {
        return 1;
    } elseif ($agree && !$agreePos) {
        return -1;
    } else {
        return 0;
    }
}

function MinMisery($dbi, $page, $users, $active_userid)
{
    //Returns the minimum rating for the page
    //from all the users.

    $cur_page = $page;

    $my_ratings_iter = $dbi->get_rating(0, $active_userid, $page);
    $my_ratings_single = $my_ratings_iter->next();
    $cur_rating = $my_ratings_single['ratingvalue'];

    $min = $cur_rating;
    foreach ($users as $buddy) {
        $buddy_rating_iter = $dbi->get_rating(0, $buddy, $cur_page);
        $buddy_rating_array = $buddy_rating_iter->next();
        $buddy_rating = $buddy_rating_array['ratingvalue'];
        if ($buddy_rating != "" && $buddy_rating < $min) {
            $min = $buddy_rating;
        }
    }
    return $min;
}

function AverageRating($dbi, $page, $users, $active_userid)
{
    //Returns the average rating for the page
    //from all the users.

    $cur_page = $page;

    $my_ratings_iter = $dbi->get_rating(0, $active_userid, $page);
    $my_ratings_single = $my_ratings_iter->next();
    $cur_rating = $my_ratings_single['ratingvalue'];
    if ($cur_rating != "") {
        $total = $cur_rating;
        $count = 1;
    } else {
        $total = 0;
        $count = 0;
    }
    foreach ($users as $buddy) {
        $buddy_rating_iter = $dbi->get_rating(0, $buddy, $cur_page);
        $buddy_rating_array = $buddy_rating_iter->next();
        $buddy_rating = $buddy_rating_array['ratingvalue'];
        if ($buddy_rating != "") {
            $total = $total + $buddy_rating;
            $count++;
        }
    }
    if ($count == 0) {
        return 0;
    } else {
        return $total / $count;
    }
}

// $Log: Buddy.php,v $
// Revision 1.3  2004/11/21 11:59:26  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.2  2004/11/15 16:00:02  rurban
// enable RateIt imgPrefix: '' or 'Star' or 'BStar',
// enable blue prediction icons,
// enable buddy predictions.
//
// Revision 1.1  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
