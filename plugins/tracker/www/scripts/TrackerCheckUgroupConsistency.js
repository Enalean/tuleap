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

    function disableFeedback(check_consistency_feedback) {
        check_consistency_feedback.hide();
    }

    function enableFeedback(check_consistency_feedback) {
        check_consistency_feedback.show();
    }

    function observeCreateModeChanges(modes, check_consistency_feedback) {
        modes.each(function (mode) {
            mode.observe('click', function () {
                if (mode.value == 'gallery' && mode.checked) {
                    disableFeedback(check_consistency_feedback);
                } else {
                    enableFeedback(check_consistency_feedback);
                }
            });
        });
    }

    function observeProjectSelectorChanges(select_project) {
        select_project.observe('change', function () {
            disableFeedback(check_consistency_feedback);
        });
    }

    function observeTemplateSelectorChanges(group_id, select_template, select_project, create_new_tracker_btn, check_consistency_feedback) {
        select_template.observe('change', function () {
            var template_group_id   = $F(select_project),
                template_tracker_id = $F(select_template);

            create_new_tracker_btn.disable();
            new Ajax.Updater(
                check_consistency_feedback,
                '/plugins/tracker/index.php',
                {
                    parameters: {
                        group_id: group_id,
                        func: 'check_ugroup_consistency',
                        template_group_id: template_group_id,
                        template_tracker_id: template_tracker_id
                    },
                    onComplete: function () {
                        enableFeedback(check_consistency_feedback);
                        create_new_tracker_btn.enable();
                    }
                }
            );
        });
    }

    var select_template = $('tracker_list_trackers_from_project');

    if (select_template) {
        var form                       = select_template.up('form'),
            modes                      = form.select('input[name=create_mode]'),
            select_project             = $('tracker_new_project_list'),
            create_new_tracker_btn     = $('create_new_tracker_btn'),
            group_id                   = form.down('input[name=group_id]').value,
            check_consistency_feedback = $('check_consistency_feedback');;

        observeCreateModeChanges(modes, check_consistency_feedback);
        observeProjectSelectorChanges(select_project);
        observeTemplateSelectorChanges(group_id, select_template, select_project, create_new_tracker_btn, check_consistency_feedback);
    }
});
