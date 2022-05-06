<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

$overrides['LocalSettingsGenerator'] = 'TuleapLocalSettingsGenerator';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TuleapLocalSettingsGenerator extends LocalSettingsGenerator
{
    public function getText(): string
    {
        $this->values['wgDefaultSkin'] = 'tuleap';
        $ls                            = parent::getText();
        $tuleapFarm                    = <<<'HERE'
### Load local configuration varibales ###
require_once( "$IP/LocalSettings.local.php" );
### Farm Instance Configuration Dispatcher ###
$GLOBALS['wgTuleapFarmConfig'] = [
	'instanceDir' => '/data/lib/tuleap/mediawiki-standalone/projects/'
];
require_once( "$IP/extensions/TuleapWikiFarm/TuleapWikiFarm.setup.php" );
HERE;

        return $ls . $tuleapFarm;
    }
}
