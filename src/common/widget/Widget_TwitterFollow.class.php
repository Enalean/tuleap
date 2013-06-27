<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Widget_TwitterFollow.class.php');
require_once('Widget.class.php');
require_once('common/date/DateHelper.class.php');

/**
* Widget_TwitterFollow
* 
* Allow to follow a twitter user
* 
*/
class Widget_TwitterFollow extends Widget {
    
    protected $twitterfollow_user;
    protected $twitterfollow_title;
    
    function Widget_TwitterFollow($id, $owner_id, $owner_type) {
        $this->Widget($id);
        $this->setOwner($owner_id, $owner_type);
    }
    function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->twitterfollow_title ?  $hp->purify($this->twitterfollow_title, CODENDI_PURIFIER_CONVERT_HTML)  : 'Twitter Follow';
    }
    function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $content = '';
        if ($this->twitterfollow_user) {
            require_once('common/rss/libs/SimplePie/simplepie.inc');
            if (!is_dir($GLOBALS['codendi_cache_dir'] .'/rss')) {
                mkdir($GLOBALS['codendi_cache_dir'] .'/rss');
            }
            $twitterfollow = new SimplePie($this->getFeedUrl($this->twitterfollow_user), $GLOBALS['codendi_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
            $max_items = 10;
            $items = array_slice($twitterfollow->get_items(), 0, $max_items);
            $content .= '<table width="100%">';
            $i = 0;
            foreach($items as $i => $item) {
                $content .= '<tr class="'. util_get_alt_row_color($i++) .'"><td WIDTH="99%">';
                $content .= '<div style="float:right;">';
                $content .=  '<a title="Reply" href="'. $this->getReplyToUrl($this->twitterfollow_user, basename($item->get_link())) .'">';
                $content .=  $GLOBALS['HTML']->getImage('ic/twitter_reply.gif');
                $content .=  '</a>';
                $content .= '</div>';
                
                $content .= '<span';
                if ($i == 1) {
                    $content .= ' style="font-size:1.5em">';
                    if ($image = $item->get_link(0, 'image')) {
                        //hack to display twitter avatar
                        $image = preg_replace('/_normal\.(jpg|png|gif)$/i', '_bigger.$1', $image);
                        $content .= '<a href="http://twitter.com/'. urlencode($this->twitterfollow_user) .'">';
                        $content .= '<img src="'.  $hp->purify($image, CODENDI_PURIFIER_CONVERT_HTML)  .'" width="48" height="48" style="float:left; margin-right:1em;" />';
                        $content .= '</a>';
                    }
                } else {
                    $content .= '>'; //end of <span
                }
                $content .=  $hp->purify($item->get_title(), CODENDI_PURIFIER_STRIP_HTML) ;
                if ($item->get_date()) {
                    $content .= ' <span style="color:#999; white-space:nowrap;" title="'. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $item->get_date('U')) .'">- '. DateHelper::timeAgoInWords($item->get_date('U')) .'</span>';
                }
                $content .= '</span>';
                
                $content .= '</td></tr>';
            }
            $content .= '</table>';
        }
        return $content;
    }
    function isAjax() {
        return true;
    }
    function hasPreferences() {
        return true;
    }
    function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="twitterfollow[title]" value="'. $hp->purify($this->twitterfollow_title, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Find tweets from the user:</td><td><input type="text" class="textfield_medium" name="twitterfollow[user]" value="'. $hp->purify($this->twitterfollow_user, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Find tweets from the user:</td><td><input type="text" class="textfield_medium" name="twitterfollow[user]" value="" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO widget_twitterfollow (owner_id, owner_type, title, user) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, user
        FROM widget_twitterfollow
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM widget_twitterfollow WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->twitterfollow_title = $data['title'];
            $this->twitterfollow_user   = $data['user'];
            $this->content_id = $id;
        }
    }
    function create($request) {
        $content_id = false;
        $vUser = new Valid_String('user');
        $vUser->setErrorMessage("Can't add empty twitterfollow user");
        $vUser->required();
        if($request->validInArray('twitterfollow', $vUser)) {
            $twitterfollow = $request->get('twitterfollow');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('twitterfollow', $vTitle)) {
                require_once('common/rss/libs/SimplePie/simplepie.inc');
                if (!is_dir($GLOBALS['codendi_cache_dir'] .'/twitterfollow')) {
                    mkdir($GLOBALS['codendi_cache_dir'] .'/twitterfollow');
                }
                $twitterfollow_reader = new SimplePie($this->getFeedUrl($twitterfollow['user']), $GLOBALS['codendi_cache_dir'] .'/twitterfollow', null, $GLOBALS['sys_proxy']);
                $twitterfollow['title'] = $twitterfollow_reader->get_title();
            }
            $sql = 'INSERT INTO widget_twitterfollow (owner_id, owner_type, title, user) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($twitterfollow['title']) ."', '". db_escape_string($twitterfollow['user']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences($request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($twitterfollow = $request->get('twitterfollow')) && $request->valid($vContentId)) {
            $vUser = new Valid_String('user');
            if($request->validInArray('twitterfollow', $vUser)) {
                $user = " user   = '". db_escape_string($twitterfollow['user']) ."' ";
            } else {
                $user = '';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('twitterfollow', $vTitle)) {
                $title = " title = '". db_escape_string($twitterfollow['title']) ."' ";
            } else {
                $title = '';
            }

            if ($user || $title) {
                $sql = "UPDATE widget_twitterfollow SET ". $title .", ". $user ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_twitterfollow WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
    
    function getCategory() {
        return 'general';
    }
    function getFeedUrl($user) {
        return 'http://search.twitter.com/search.atom?q=+from:'. $user;
    }
    function getReplyToUrl($user, $status_id) {
        return 'http://twitter.com/home?status=@'. urlencode($user) .'%20&in_reply_to_status_id='. urlencode($status_id) .'&in_reply_to='. urlencode($user);
    }
}
?>
