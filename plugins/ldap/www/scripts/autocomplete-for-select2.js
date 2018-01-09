/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { select2 } from 'tlp';
import { escaper } from 'escaper';

export {
    autocomplete_groups_for_select2
};

function autocomplete_groups_for_select2(element, options) {
    const real_options = {
        placeholder: element.dataset.placeholder || '',
        minimumInputLength: 3,
        tags: true,
        ajax: {
            url     : '/plugins/ldap/autocomplete.php',
            dataType: 'json',
            delay   : 250,
            data: function({ term, page = 1 }) {
                return {
                    ldap_group_name: term,
                    page
                };
            }
        },
        escapeMarkup  : markup => markup,
        templateResult: (ldap_group) => {
            if (ldap_group.loading) {
                return ldap_group.text;
            }

            return `<i class="autocomplete-ldap-group-icon fa fa-group"></i>  ${ escaper.html(ldap_group.text) }`;
        },
        ...options
    };

    return select2(element, real_options);
}
