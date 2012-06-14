<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Information necessary to build a QrCode that links to a cardwall
 */
class Cardwall_QrCode {

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $url;

    /**
     * @var int
     */
    public $width = 150;

    /**
     * @var int
     */
    public $height = 150;

    /**
     * @param string $request_uri '/plugins/agiledashboard/?group_id=1&action=show&....
     */
    public function __construct($request_uri) {
        $proto       = Config::get('sys_force_ssl') ? 'https' : 'http';
        $url         = $proto .'://'. Config::get('sys_default_domain') . $request_uri;
        $dimensions  = $this->width .'x'. $this->height;
        $this->class = Toggler::getClassname('plugin_cardwall_qrcode_toggler', false, true);
        $this->url   = 'http://chart.apis.google.com/chart?'. http_build_query(
            array(
                'chs'  => $dimensions,
                'cht'  => 'qr',
                'chld' => 'L|0',
                'chl'  => $url,
            )
        );
    }
}
?>
