/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

const path = require('path');

const path_to_tuleap_root = path.resolve(__dirname, '../../../');

const tlp_fetch_alias = {
    'tlp-fetch': path.join(
        path_to_tuleap_root,
        'src/www/themes/common/tlp/src/js/fetch-wrapper.js'
    )
};

const flaming_parrot_labels_box_alias = {
    'labels-box': path.join(
        path_to_tuleap_root,
        'src/www/scripts/labels/labels-box.js'
    )
};

const flaming_parrot_labels_box_aliases = Object.assign(
    {},
    tlp_fetch_alias,
    flaming_parrot_labels_box_alias
);

function extendAliases(...aliases) {
    return Object.assign({}, ...aliases);
}

module.exports = {
    extendAliases,
    tlp_fetch_alias,
    flaming_parrot_labels_box_aliases
};
