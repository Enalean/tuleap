#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

$source_openfire_auth_provider      = '/usr/share/codendi/plugins/IM/include/jabbex_api/installation/resources/codendi_auth.jar';
$destination_openfire_auth_provider = '/opt/openfire/lib/codendi_auth.jar';
if (copy($source_openfire_auth_provider, $destination_openfire_auth_provider)) {
    chmod($destination_openfire_auth_provider, 644);
    chown($destination_openfire_auth_provider, 'codendiadm');
    chgrp($destination_openfire_auth_provider, 'codendiadm');
    print('The authentication provider have been successfully redeployed.');
    print('You can now restart Openfire with: # service openfire restart');
} else {
    file_put_contents('php://stderr', "The authentication provider can not be copied.\n");
}