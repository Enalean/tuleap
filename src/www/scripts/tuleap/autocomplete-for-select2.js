/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

var tuleap  = tuleap || { };

tuleap.autocomplete_projects_for_select2 = function(element, options) {
    options = options || {};

    options.include_private_projects = options.include_private_projects || 0;
    options.placeholder              = element.dataset.placeholder || '';
    options.minimumInputLength       = 3;
    options.allowClear               = true;
    options.debug                    = true;
    options.ajax                     = {
        url     : '/project/autocomplete.php',
        dataType: 'json',
        delay   : 250,
        data    : function(params) {
            return {
                return_type: 'json_for_select_2',
                name       : params.term,
                page       : params.page || 1,
                private    : options.include_private_projects
            };
        }
    };

    tlp.select2(element, options);
};

tuleap.autocomplete_users_for_select2 = function(element, options) {
    options = options || {};

    options.internal_users_only = options.internal_users_only || 0;
    options.placeholder         = element.dataset.placeholder || '';
    options.minimumInputLength  = 3;
    options.allowClear          = true;
    options.ajax                = {
        url     : '/user/autocomplete.php',
        dataType: 'json',
        delay   : 250,
        data    : function(params) {
            return {
                return_type      : 'json_for_select_2',
                name             : params.term,
                page             : params.page || 1,
                codendi_user_only: options.internal_users_only
            };
        }
    };

    tlp.select2(element, options);
};
