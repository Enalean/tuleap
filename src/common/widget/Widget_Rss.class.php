<?php

require_once('Widget.class.php');

/**
* Widget_Rss
* 
* Rss reader
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
/* abstract */ class Widget_Rss extends Widget {
    var $rss_title;
    var $rss_url;
    function Widget_Rss($id, $owner_id, $owner_type) {
        $this->Widget($id);
        $this->setOwner($owner_id, $owner_type);
    }
    function getTitle() {
        $hp = CodeX_HTMLPurifier::instance();
        return $this->rss_title ?  $hp->purify($this->rss_title, CODEX_PURIFIER_CONVERT_HTML)  : 'RSS Reader';
    }
    function getContent() {
        $content = '';
        if ($this->rss_url) {
            require_once('common/rss/libs/SimplePie/simplepie.inc');
            if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                mkdir($GLOBALS['codex_cache_dir'] .'/rss');
            }
            $rss =& new SimplePie($this->rss_url, $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
            $max_items = 10;
            $items = array_slice($rss->get_items(), 0, $max_items);
            $content .= '<table width="100%">';
            $i = 0;
            foreach($items as $item) {
                $content .= '<tr class="'. util_get_alt_row_color($i++) .'"><td WIDTH="99%">';
                $content .= '<a href="'. $item->get_link() .'">'. $item->get_title() .'</a>';
                if ($item->get_date()) {
                    $content .= '<span style="color:#999;" title="'. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $item->get_date('U')) .'"> - '. util_time_ago_in_words($item->get_date('U')) .'</span>';
                }
                $content .= '</td></tr>';
            }
            $content .= '</table>';
        }
        return $content;
    }
    function getPreferences() {
        $hp = CodeX_HTMLPurifier::instance();
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="rss[title]" value="'. $hp->purify($this->rss_title, CODEX_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="rss[url]" value="'. $hp->purify($this->rss_url, CODEX_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="rss[url]" value="'. $GLOBALS['Language']->getText('widget_rss', 'default_url') .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO widget_rss (owner_id, owner_type, title, url) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, url
        FROM widget_rss
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM widget_rss WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->rss_title = $data['title'];
            $this->rss_url   = $data['url'];
            $this->content_id = $id;
        }
    }
    function create(&$request) {
        $content_id = false;
        $vUrl = new Valid_String('url');
        $vUrl->setErrorMessage("Can't add empty rss url");
        $vUrl->required();
        if($request->validInArray('rss', $vUrl)) {
            $rss = $request->get('rss');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('rss', $vTitle)) {
                require_once('common/rss/libs/SimplePie/simplepie.inc');
                if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                    mkdir($GLOBALS['codex_cache_dir'] .'/rss');
                }
                $rss_reader =& new SimplePie($rss['url'], $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
                $rss['title'] = $rss_reader->get_title();
            }
            $sql = 'INSERT INTO widget_rss (owner_id, owner_type, title, url) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($rss['title']) ."', '". db_escape_string($rss['url']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(&$request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($rss = $request->get('rss')) && $request->valid($vContentId)) {
            $vUrl = new Valid_String('url');
            if($request->validInArray('rss', $vUrl)) {
                $url = " url   = '". db_escape_string($rss['url']) ."' ";
            } else {
                $url = '';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('rss', $vTitle)) {
                $title = " title = '". db_escape_string($rss['title']) ."' ";
            } else {
                $title = '';
            }

            if ($url || $title) {
                $sql = "UPDATE widget_rss SET ". $title .", ". $url ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_rss WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
}
?>
