/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

var ProjectHistory = Class.create({
    initialize: function (sub_events_array, selected_sub_events) {
        if (!sub_events_array) {
            throw 'sub_events_array is mandatory!';
        }
        this.sub_events_array = sub_events_array;
        var title = $('history_search_title');
        title.observe('click', this.toggleForm);
        // We may make the form hidden by default
        //$('project_history_search').hide();
        Event.observe($('events_box'), 'change', this.SelectSubEvent.bindAsEventListener(this));
        // Load sub events content when page loads
        this.SelectSubEvent(selected_sub_events);
     },
    toggleForm: function() {
        // Toggle search form
        $('project_history_search').toggle();
        // Switch icon plus/minus
        var icon = $('toggle_form_icon');
        if (icon.src.indexOf('toggle_plus.png') != -1) {
            icon.src = icon.src.replace('toggle_plus.png', 'toggle_minus.png');
        } else {
            icon.src = icon.src.replace('toggle_minus.png', 'toggle_plus.png');
        }
    },
    SelectSubEvent: function(selected_sub_events) {
        this.removeAllOptions($('sub_events_box'));
        this.addOption('choose_event');
        //Try to use a loop instead
        //Permission section
        if ($('events_box').value == 'Permissions') {
            this.addOption("perm_reset_for_field", selected_sub_events['perm_reset_for_field']);
            this.addOption("perm_reset_for_tracker", selected_sub_events['perm_reset_for_tracker']);
            this.addOption("perm_reset_for_package", selected_sub_events['perm_reset_for_package']);
            this.addOption("perm_reset_for_release", selected_sub_events['perm_reset_for_release']);
            this.addOption("perm_reset_for_document", selected_sub_events['perm_reset_for_document']);
            this.addOption("perm_reset_for_folder", selected_sub_events['perm_reset_for_folder']);
            this.addOption("perm_reset_for_docgroup", selected_sub_events['perm_reset_for_docgroup']);
            this.addOption("perm_reset_for_wiki", selected_sub_events['perm_reset_for_wiki']);
            this.addOption("perm_reset_for_wikipage", selected_sub_events['perm_reset_for_wikipage']);
            this.addOption("perm_reset_for_wikiattachment", selected_sub_events['perm_reset_for_wikiattachment']);
            this.addOption("perm_reset_for_object", selected_sub_events['perm_reset_for_object']);
            this.addOption("perm_granted_for_field", selected_sub_events['perm_granted_for_field']);
            this.addOption("perm_granted_for_tracker", selected_sub_events['perm_granted_for_tracker']);
            this.addOption("perm_granted_for_package", selected_sub_events['perm_granted_for_package']);
            this.addOption("perm_granted_for_release", selected_sub_events['perm_granted_for_release']);
            this.addOption("perm_granted_for_document", selected_sub_events['perm_granted_for_document']);
            this.addOption("perm_granted_for_folder", selected_sub_events['perm_granted_for_folder']);
            this.addOption("perm_granted_for_docgroup", selected_sub_events['perm_granted_for_docgroup']);
            this.addOption("perm_granted_for_wiki", selected_sub_events['perm_granted_for_wiki']);
            this.addOption("perm_granted_for_wikipage", selected_sub_events['perm_granted_for_wikipage']);
            this.addOption("perm_granted_for_wikiattachment", selected_sub_events['perm_granted_for_wikiattachment']);
            this.addOption("perm_granted_for_object", selected_sub_events['perm_granted_for_object']);
        }

        //Project section
        if ($('events_box').value == "Project") {
            this.addOption("rename_done", selected_sub_events['rename_done']);
            this.addOption("rename_with_error", selected_sub_events['rename_with_error']);
            this.addOption("approved", selected_sub_events['approved']);
            this.addOption("deleted", selected_sub_events['deleted']);
            this.addOption("rename_request", selected_sub_events['rename_request']);
            this.addOption("is_public", selected_sub_events['is_public']);
            this.addOption("group_type", selected_sub_events['group_type']);
            this.addOption("http_domain", selected_sub_events['http_domain']);
            this.addOption("unix_box", selected_sub_events['unix_box']);
            this.addOption("changed_public_info", selected_sub_events['changed_public_info']);
            this.addOption("changed_trove", selected_sub_events['changed_trove']);
            this.addOption("membership_request_updated", selected_sub_events['membership_request_updated']);
            this.addOption("import", selected_sub_events['import']);
            this.addOption("mass_change", selected_sub_events['mass_change']);
        }

        //User group section
       if ($('events_box').value == "User Group") {
            this.addOption("upd_ug", selected_sub_events['upd_ug']);
            this.addOption("del_ug", selected_sub_events['del_ug']);
            this.addOption("changed_member_perm", selected_sub_events['changed_member_perm']);
        }

        //Users section
        if ($('events_box').value == "Users") {
            this.addOption("changed_personal_email_notif", selected_sub_events['changed_personal_email_notif']);
            this.addOption("added_user", selected_sub_events['added_user']);
            this.addOption("removed_user", selected_sub_events['removed_user']);
        }

        //Uncatogorised items section
        if ($('events_box').value == "Others") {
            this.addOption("changed_bts_form_message", selected_sub_events['changed_bts_form_message']);
            this.addOption("changed_bts_allow_anon", selected_sub_events['changed_bts_allow_anon']);
            this.addOption("changed_patch_mgr_settings", selected_sub_events['changed_patch_mgr_settings']);
            this.addOption("changed_task_mgr_other_settings", selected_sub_events['changed_task_mgr_other_settings']);
            this.addOption("changed_sr_settings", selected_sub_events['changed_sr_settings']);
        }
    },
    removeAllOptions: function(selectbox) {
        var i;
        for (i = selectbox.options.length-1; i>=0; i--) {
            selectbox.remove(i);
        }
    },
    addOption: function(value, selected) {
        var optn = Builder.node('option', {'value' : value}, this.sub_events_array[value]);
        $('sub_events_box').appendChild(optn);
        if (selected) {
            optn.selected = true;
        } else {
            optn.selected = false;
        }
    }
});