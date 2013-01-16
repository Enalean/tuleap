<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * Use This file to add stuff in the service top bar
 */
$additional_tabs = array(
    // // This example adds a service thats link to a specific project
    // array(
    //     'selected' => (boolean) (strstr(getStringFromServer('REQUEST_URI'),'/projects/gpig/')),
    //     'link'     => '/projects/gpig/',
    //     'title'    => 'Guinea Pig',
    // ),
    array(
        'selected' => false,
        'link'     => '/#screenshots',
        'title'    => 'Features & Screenshots',
    ),
);
$wiki_install_url = '/wiki/?group_id=101&pagename=Installation+%26+Administration%2FHow+to+install';
$additional_tabs[] = array(
    'selected' => (boolean) (strstr(getStringFromServer('REQUEST_URI'), $wiki_install_url)),
    'link'     => $wiki_install_url,
    'title'    => 'Get Tuleap',
);
$wiki_contribute_url = '/wiki/?group_id=101&pagename=ContributePageToBeDefined';
$additional_tabs[] = array(
    'selected' => (boolean) (strstr(getStringFromServer('REQUEST_URI'), $wiki_contribute_url)),
    'link'     => $wiki_contribute_url,
    'title'    => 'Contribute',
);
?>
