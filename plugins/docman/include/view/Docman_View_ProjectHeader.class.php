<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_ProjectHeader
*/

require_once('Docman_View_Header.class.php');

/* abstract */ class Docman_View_ProjectHeader extends Docman_View_Header {
    
    /* protected */ function _scripts($params) {
        echo '<script type="text/javascript"> var docman = new com.xerox.codex.Docman('. $params['group_id'] .', ';
        $di =& $this->_getDocmanIcons($params);
        echo $this->phpArrayToJsArray(array_merge(array(
                'folderSpinner' => $di->getFolderSpinner(),
                'spinner'       => $di->getSpinner(),
                'language'      => array(
                    'btn_close'                => $GLOBALS['Language']->getText('global','btn_close'),
                    'new_in'                   => $GLOBALS['Language']->getText('plugin_docman','new_in'),
                    'new_other_folders'        => $GLOBALS['Language']->getText('plugin_docman','new_other_folders'),
                    'new_same_perms_as_parent' => $GLOBALS['Language']->getText('plugin_docman','new_same_perms_as_parent'),
                    'new_view_change'          => $GLOBALS['Language']->getText('plugin_docman','new_view_change'),
                    'new_news_explaination'    => $GLOBALS['Language']->getText('plugin_docman','new_news_explain'),
                    'new_news_displayform'     => $GLOBALS['Language']->getText('plugin_docman','new_news_displayform'),
                )
            ),
            $this->_getJSDocmanParameters($params)
        ));
        echo '); </script>';
    }
    
    /* protected */ function _getAdditionalHtmlParams($params) {
        return  array(
            'group'  => $params['group_id'],
            'toptab' => 'docman');
    }

    
    /* protected */ function _getJSDocmanParameters($params) {
        return array();
    }
    
    function phpArrayToJsArray($array) {
        if (is_array($array)) {
            if (count($array)) {
                $output = '{';
                reset($array);
                $comma = '';
                do {
                    if(list($key, $value) = each($array)) {
                        $output .= $comma . $key .': '. $this->phpArrayToJsArray($value);
                        $comma = ', ';
                    }
                } while($key);
                $output .= '}';
            }
        } else if (is_bool($array)) {
            $output = $array?'true':'false';
        } else {
            $output = "'". addslashes($array) ."'";
        }
        return $output;
    }
}

?>
