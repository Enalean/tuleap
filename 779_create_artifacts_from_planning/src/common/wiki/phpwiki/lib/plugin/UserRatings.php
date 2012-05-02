<?php // -*-php-*-
rcs_id('$Id: UserRatings.php,v 1.4 2005/09/30 18:41:39 uckelman Exp $');
/**
 Copyright 2004 Dan Frankowski

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 */

require_once('lib/PageList.php');
require_once('lib/wikilens/Buddy.php');
require_once("lib/wikilens/PageListColumns.php");

if (ENABLE_USER_NEW)
    require_once("lib/WikiUserNew.php");
else
    require_once("lib/WikiUser.php");

/**
 * Show a user's ratings in a table, using PageList.
 * Usage:
 * <?plugin UserRatings ?>
 *
 * This only works with the "wikilens" theme.
 */

class WikiPlugin_UserRatings
extends WikiPlugin
{
    function getName () {
        return _("UserRatings");
    }

    function getDescription () {
        return _("List the user's ratings.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.4 $");
    }

    function getDefaultArguments() {
        return array('dimension' => '0',
                     // array of userids to display ratings for; null/empty for 
                     // active user only
                     'userids'  => null,
                     // array of pageids to display ratings for; null for all
                     // of current active user's ratings
                     'pageids'  => null,
                     // a category to display ratings for; null for no category;
                     // has higher precedence than pageids
                     'category' => null,
                     'pagename' => '[pagename]', // hackish
                     'exclude'  => '',
                     'limit'    => 0, // limit of <=0 is show-all
                     'noheader' => 0,
                     'userPage' => false,
                     'nobuds'     => false,
                     // rating columns are added later
                     'info'     => 'pagename');
                     // getting a bit crowded with the buddies...
                     // 'info'     => 'hits,pagename,author,ratingwidget');
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    function run($dbi, $argstr, &$request, $basepage) {
        extract($this->getArgs($argstr, $request));

        // XXX: fix to reflect multi-user ratings?
        $caption = _("Displaying %d ratings:");

        $active_user   = $request->getUser();
        $active_userid = $active_user->_userid;
        
        // check for request to display a category's ratings
        if(isset($category) && is_string($category) && strlen($category))
        {
            $pageids = array();
            $category_page = $dbi->getPage($category);
            $iter = $category_page->getLinks();
            while($item = $iter->next())
            {
                array_push($pageids, $item->getName());
            }
            // XXX: is there a way to retrieve the preferred plural 
            // representation of a category name?
            // XXX: should the category text be a link?  can it be one easily?
            
            $caption = sprintf(_("Showing all %ss (%%d):"), $category);
        }
        // if null is passed as the pageids argument and no category was passed,
        // show active user's ratings
        elseif(!isset($pageids) || !is_array($pageids))
        {
            // XXX: need support for sorted ratings
            // bug: pages excluded from the PageList via the "exclude" argument 
            // count toward the limit!
            $pageids = array();
            
            $active_user_ratings_user = & RatingsUserFactory::getUser($active_user->getId());
            $current_user_ratings = $active_user_ratings_user->get_ratings();
            
            if ($userPage){
                //we're on a user's homepage, get *their* ratings
                $this_page_user = & RatingsUserFactory::getUser($userPage);
                $caption = _("Here are $userPage" . "'s %d page ratings:");
                $ratings = $this_page_user->get_ratings();
            } else {
                $caption = _("Here are your %d page ratings:");
                $ratings = $current_user_ratings;
            }    
            
            
            $i = 0;
            foreach($ratings as $pagename => $page_ratings)
            {
                // limit is currently only honored for "own" ratings
                if($limit > 0 && $i >= $limit)
                {
                    break;
                }
                if(isset($page_ratings[$dimension]))
                {
                    array_push($pageids, $pagename);
                    $i++;
                }
            }
           // $caption = _("Here are your %d page ratings:");
           //make $ratings the user's ratings again if it had been treated as the current page
           // name's ratings
           $ratings = $current_user_ratings;
        }

        // if userids is null or empty, fill it with just the active user
        if(!isset($userids) || !is_array($userids) || !count($userids))
        {
            // TKL: moved getBuddies call inside if statement because it was
            // causing the userids[] parameter to be ignored
            if(is_string($active_userid) && strlen($active_userid) && $active_user->isSignedIn() && !$userPage) {
                if (isset($category_page)){
                    $userids = getBuddies($active_userid, $dbi, $category_page->getName());
                } else {
                   $userids = getBuddies($active_userid, $dbi);
                } 
            }
            elseif ($userPage)
            {
                //we're on a user page, show that user's ratings as the only column
                $userids = array();
                array_push($userids, $userPage);   
            }
            else
            {
                $userids = array();
                // XXX: this wipes out the category caption...
                // $caption = _("You must be logged in to view ratings.");
            }
        }

        // find out which users we should show ratings for
        
        // users allowed in the prediction calculation
        $allowed_users = array();
        // users actually allowed to be shown to the user
        $allowed_users_toshow = array();
        foreach($userids as $userid)
        {
            $user = & RatingsUserFactory::getUser($userid);
            if($user->allow_view_ratings($active_user))
            {
                array_push($allowed_users_toshow, $user);
            }
            // all users should be allowed in calculation
            array_push($allowed_users, $user);
            // This line ensures $user is not a reference type after this loop
            // If it is a reference type, that can produce very unexpected behavior!
            unset($user);
        }
        // if no buddies, use allusers in prediction calculation
        
        if (count($userids) == 0 || $userPage){
           $allowed_users = array();
           //$people_iter = $dbi->get_users_rated();
            $people_dbi = RatingsDb::getTheRatingsDb();
            $people_iter = $people_dbi->sql_get_users_rated();
            while($people_array = $people_iter->next()){
                $userid = $people_array['pagename']; 
                $user = & RatingsUserFactory::getUser($userid);
                array_push($allowed_users, $user);
            }
            
         }
        

        $columns = $info ? explode(",", $info) : array();
        // build our table...
        $pagelist = new PageList($columns, $exclude, array('dimension' => $dimension, 'users' => $allowed_users_toshow));

        // augment columns
        //$preds = new _PageList_Column_prediction('prediction', _("Pred"), 'right', $dimension, $allowed_users);
        $preds = array('_PageList_column_prediction','custom:prediction', _("Pred"),'right',' ' , $allowed_users);
        $pagelist->addColumnObject($preds);
        
        //$widget = new _PageList_Column_ratingwidget('ratingwidget', _("Rate"), 'left', $dimension);        
        $widget = array('_PageList_column_ratingwidget','custom:ratingwidget', _("Rate"), 'center');
        $pagelist->addColumnObject($widget);
        
        $noRatingUsers = array();
        if (!$nobuds){
            foreach($allowed_users_toshow as $idx => $user) {
                // For proper caching behavior, get a ref, don't user $user
                $u = & $allowed_users_toshow[$idx];
                //$col = & new _PageList_Column_ratingvalue('ratingvalue', $u->getId(), 'right', $dimension, $u);
                $col = array('_PageList_Column_ratingvalue','custom:ratingvalue', $u->getId(), 'right',' ' ,$u);
                $pagelist->addColumnObject($col);
                unset($u);
            }
        }

        // add rows -- each row represents an item (page)
        foreach($pageids as $pagename)  {
            // addPage can deal with cases where it is passed a string
            $pagelist->addPage($pagename);
        }
        
        if (! $noheader) {
            $pagelist->setCaption(_($caption));
        }
        

        return $pagelist;
    }
};


// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
