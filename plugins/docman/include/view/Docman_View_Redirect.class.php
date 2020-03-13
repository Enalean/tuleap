<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_Redirect
*/

require_once('Docman_View_View.class.php');

class Docman_View_Redirect extends Docman_View_View /* implements Visitor */
{

    /* protected */ public function _content($params)
    {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } elseif (isset($params['item'])) {
            $event_manager = EventManager::instance();
            $event_manager->processEvent('plugin_docman_event_access', array(
                'group_id' => $params['group_id'],
                'item'     => &$params['item'],
                'user'     => &$params['user']
            ));
            $url = $params['item']->accept($this, $params);
        } else {
            $url = '/';
        }

        $GLOBALS['Response']->redirect($url);
    }
    public function visitFolder(&$item, $params = array())
    {
        trigger_error('Redirect view cannot be applied to Folders');
    }
    public function visitWiki(&$item, $params = array())
    {
        $project_id = $item->getGroupId();
        $pagename   = urlencode($item->getPagename());
        return '/wiki/?group_id=' . $project_id . '&pagename=' . $pagename;
    }

    public function visitLink(&$item, $params = array())
    {
        if (isset($params['version_number'])) {
            $version_factory = new Docman_LinkVersionFactory();

            $version = $version_factory->getSpecificVersion($item, $params['version_number']);
            if ($version) {
                return $version->getLink();
            }
        }

        return $item->getUrl();
    }

    public function visitFile(&$item, $params = array())
    {
        trigger_error('Redirect view cannot be applied to Files');
    }
    public function visitEmbeddedFile(&$item, $params = array())
    {
        trigger_error('Redirect view cannot be applied to Embedded Files');
    }

    public function visitEmpty(&$item, $params = array())
    {
        trigger_error('Redirect view cannot be applied to Empty documents');
    }
}
