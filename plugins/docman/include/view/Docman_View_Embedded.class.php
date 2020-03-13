<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class Docman_View_Embedded extends Docman_View_Display
{


    public function _content($params)
    {
        if (isset($params['version_number'])) {
            $version_factory = $this->_getVersionFactory($params);
            $version = $version_factory->getSpecificVersion($params['item'], $params['version_number']);
        } else {
            $version = $params['item']->getCurrentVersion();
        }
        if ($version) {
            if (file_exists($version->getPath())) {
                $event_manager = EventManager::instance();
                $event_manager->processEvent('plugin_docman_event_access', array(
                    'group_id' => $params['group_id'],
                    'item'     => &$params['item'],
                    'version'  => $version->getNumber(),
                    'user'     => &$params['user']
                ));
                $mime = explode('/', $version->getFiletype());
                if (in_array($mime[1], array('plain', 'css', 'javascript'))) {
                    $balise = 'pre';
                } else {
                    $balise = 'div';
                }
                echo '<' . $balise . ' class="docman_embedded_file_content">';
                echo $this->hp->purify(file_get_contents($version->getPath()), CODENDI_PURIFIER_FULL);
                echo '</' . $balise . '>';
            } else {
                $this->_controller->feedback->log('error', dgettext('tuleap-docman', 'The file cannot be found.'));
                $v = new Docman_View_DocmanError($this->_controller);
                $v->display($params);
            }
        }
    }
}
