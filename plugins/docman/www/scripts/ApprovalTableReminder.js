/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * Script of the approval table reminder
 */

var codendi = codendi || { };
document.observe('dom:loaded', function () {
    if ($('docman_approval_table_create_add_reviewers')) {
        var userAutocomplete = new UserAutoCompleter('user_list', codendi.imgroot, true);
        userAutocomplete.registerOnLoad();
        if (!$('approval_table_reminder_checkbox').checked) {
            Element.toggle('approval_table_occurence_form', 'slide', { duration: 0 });
        }
        $('approval_table_reminder_checkbox').observe('click', function () {
            Effect.toggle('approval_table_occurence_form', 'slide', { duration: 0 });
            Effect.toggle('approval_table_reminder', 'slide', { duration: 0 });
        });
    }
});