<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright © STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Docman_View_ProjectHeader.class.php';

class Docman_View_PasteInProgress extends Docman_View_ProjectHeader
{

    public function _getTitle($params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return sprintf(dgettext('tuleap-docman', 'Paste in progress \'%1$s\' in \'%2$s\''), $hp->purify($params['itemToPaste']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML), $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
    }

    public function _content($params)
    {
        //spinner
        echo '<p id="paste_' . $params['itemToPaste']->getId() . '">' . dgettext('tuleap-docman', 'Paste in progress...');
        $docmanIcons = $this->_getDocmanIcons(null);
        echo sprintf(dgettext('tuleap-docman', 'Please wait for the paste to complete. This may take a while depending on size of data to be copied <img src="%1$s" />'), $docmanIcons->getIcon('spinner-greenie.gif'));
        echo '</p>';

        // Flush the output buffer right now to display the spinner before the
        // paste begin.
        // For an unknown reason, ob_flush is needed, otherwise, the output is
        // stoped right after the display of service tabs!
        ob_flush();
        flush();

        // here the processing
        $actions = new Docman_Actions($this->_controller);
        $actions->doPaste($params['itemToPaste'], $params['item'], $params['rank'], $params['importMd'], $params['srcMode']);

        // Remove wait mesage and the spinner
        echo '<script type="text/javascript">$("paste_' . $params['itemToPaste']->getId() . '").hide();</script>';

        //Display paste sucessfully complete.
        echo dgettext('tuleap-docman', '<p>Paste operation successfully completed.</p>');

        $url = $this->_controller->getDefaultUrl() . 'action=show&id=' . $params['item']->getId();
        echo sprintf(dgettext('tuleap-docman', '<p>You will be redirected to <a href="%1$s"> %2$s </a> in %3$s seconds</p>'), $url, $url, 5);
        echo '<script type="text/javascript">setTimeout(function () {location.href="' . $url . '";}, 5000);</script>';
    }

    public function &_getDocmanIcons($params)
    {
        $icons = new Docman_Icons($this->_controller->getThemePath() . '/images/ic/');
        return $icons;
    }
}
