/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

document.observe('dom:loaded', function () {

    function observeCreateModeChanges(form) {
        var check_consistency_feedback = $('check_consistency_feedback');
        form.select('input[name=create_mode]').each(function (mode) {
            mode.observe('click', function () {
                if (mode.value == 'gallery' && mode.checked) {
                    check_consistency_feedback.show();
                } else {
                    check_consistency_feedback.hide();
                }
            });
        });
    }

    function observeTemplateSelectorChanges(group_id, select_template, select_project) {
        select_template.observe('change', function () { //todo: check that 'change' evt is valid on IE
            var template_group_id   = $F(select_project),
                template_tracker_id = $F(select_template);

            new Ajax.Updater(
                $('check_consistency_feedback'),
                '/plugins/tracker/index.php',
                {
                    parameters: {
                        group_id: group_id,
                        func: 'check_ugroup_consistency',
                        template_group_id: template_group_id,
                        template_tracker_id: template_tracker_id
                    }
                }
            );
        });
    }

    var select_template = $('tracker_list_trackers_from_project'),
        select_project  = $('tracker_new_project_list');

    if (select_template) {
        var form = select_template.up('form');
            group_id = form.down('input[name=group_id]').value;

        observeCreateModeChanges(form);
        observeTemplateSelectorChanges(group_id, select_template, select_project);
    }
});
