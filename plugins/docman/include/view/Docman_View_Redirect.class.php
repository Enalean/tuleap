<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_View_Redirect extends Docman_View_View /* implements Visitor */
{

    /* protected */ public function _content($params)
    {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } elseif (isset($params['item'])) {
            $event_manager = EventManager::instance();
            $event_manager->processEvent('plugin_docman_event_access', [
                'group_id' => $params['group_id'],
                'item'     => &$params['item'],
                'user'     => &$params['user']
            ]);
            $url = $params['item']->accept($this, $params);
        } else {
            $url = '/';
        }

        $GLOBALS['Response']->redirect($url);
    }
    public function visitFolder(&$item, $params = [])
    {
        trigger_error('Redirect view cannot be applied to Folders');
    }
    public function visitWiki(&$item, $params = [])
    {
        $project_id = urlencode($item->getGroupId());
        $pagename   = urlencode($item->getPagename());
        return '/wiki/?group_id=' . $project_id . '&pagename=' . $pagename;
    }

    public function visitLink(&$item, $params = [])
    {
        $url = null;
        if (isset($params['version_number'])) {
            $version_factory = new Docman_LinkVersionFactory();

            $version = $version_factory->getSpecificVersion($item, $params['version_number']);
            if ($version) {
                $url = $version->getLink();
            }
        }

        if ($url === null) {
            $url = $item->getUrl();
        }

        $valid_localuri = new Valid_LocalURI();
        $valid_ftp      = new Valid_FTPURI();
        if (! $valid_localuri->validate($url) && ! $valid_ftp->validate($url)) {
            return '/';
        }

        /**
         * @psalm-taint-escape text
         */
        $header = 'Location: ' . $url;
        header($header);
        exit();
    }

    public function visitFile(&$item, $params = [])
    {
        trigger_error('Redirect view cannot be applied to Files');
    }
    public function visitEmbeddedFile(&$item, $params = [])
    {
        trigger_error('Redirect view cannot be applied to Embedded Files');
    }

    public function visitEmpty(&$item, $params = [])
    {
        trigger_error('Redirect view cannot be applied to Empty documents');
    }
}
