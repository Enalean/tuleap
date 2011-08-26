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
        this.addOption('choose_event', false, true);
        //Permission section
        if ($('events_box').value == 'Permissions') {
            PermissionsSubEvents = this.getPermissionsSubEventsInArray();
            $('events_array').update('<INPUT TYPE="HIDDEN" NAME="all_sub_events" ID="all_sub_events" VALUE="'+PermissionsSubEvents+'">');
            $('events_array').innerHTML;
            for (var i = 0; i < PermissionsSubEvents.length; ++i) {
                 this.addOption(PermissionsSubEvents[i], selected_sub_events[PermissionsSubEvents[i]]);
            }
        }

        //Project section
        if ($('events_box').value == "Project") {
            ProjectSubEvents = this.getProjectSubEventsInArray();
            $('events_array').update('<INPUT TYPE="HIDDEN" NAME="all_sub_events" ID="all_sub_events" VALUE="'+ProjectSubEvents+'">');
            $('events_array').innerHTML;
            for (var i = 0; i < ProjectSubEvents.length; ++i) {
                 this.addOption(ProjectSubEvents[i], selected_sub_events[ProjectSubEvents[i]]);
            }
        }

        //User group section
       if ($('events_box').value == "User Group") {
           UserGroupSubEvents = this.getUserGroupSubEventsInArray();
           $('events_array').update('<INPUT TYPE="HIDDEN" NAME="all_sub_events" ID="all_sub_events" VALUE="'+UserGroupSubEvents+'">');
           $('events_array').innerHTML;
           for (var i = 0; i < UserGroupSubEvents.length; ++i) {
                this.addOption(UserGroupSubEvents[i], selected_sub_events[UserGroupSubEvents[i]]);
           }
        }

        //Users section
        if ($('events_box').value == "Users") {
            UsersSubEvents = this.getUsersSubEventsInArray();
            $('events_array').update('<INPUT TYPE="HIDDEN" NAME="all_sub_events" ID="all_sub_events" VALUE="'+UsersSubEvents+'">');
            $('events_array').innerHTML;
            for (var i = 0; i < UsersSubEvents.length; ++i) {
                 this.addOption(UsersSubEvents[i], selected_sub_events[UsersSubEvents[i]]);
            }
        }

        //Uncatogorised items section
        if ($('events_box').value == "Others") {
            OthersSubEvents = this.getOthersSubEventsInArray();
            $('events_array').update('<INPUT TYPE="HIDDEN" NAME="all_sub_events" ID="all_sub_events" VALUE="'+OthersSubEvents+'">');
            $('events_array').innerHTML;
            for (var i = 0; i < OthersSubEvents.length; ++i) {
                 this.addOption(OthersSubEvents[i], selected_sub_events[OthersSubEvents[i]]);
            }
        }
    },
    getPermissionsSubEventsInArray: function() {
        var PermissionsSubEvents = new Array();
        PermissionsSubEvents.push("perm_reset_for_field");
        PermissionsSubEvents.push("perm_reset_for_tracker");
        PermissionsSubEvents.push("perm_reset_for_package");
        PermissionsSubEvents.push("perm_reset_for_release");
        PermissionsSubEvents.push("perm_reset_for_document");
        PermissionsSubEvents.push("perm_reset_for_folder");
        PermissionsSubEvents.push("perm_reset_for_docgroup");
        PermissionsSubEvents.push("perm_reset_for_wiki");
        PermissionsSubEvents.push("perm_reset_for_wikipage");
        PermissionsSubEvents.push("perm_reset_for_wikiattachment");
        PermissionsSubEvents.push("perm_reset_for_object");
        PermissionsSubEvents.push("perm_granted_for_field");
        PermissionsSubEvents.push("perm_granted_for_tracker");
        PermissionsSubEvents.push("perm_granted_for_package");
        PermissionsSubEvents.push("perm_granted_for_release");
        PermissionsSubEvents.push("perm_granted_for_document");
        PermissionsSubEvents.push("perm_granted_for_folder");
        PermissionsSubEvents.push("perm_granted_for_docgroup");
        PermissionsSubEvents.push("perm_granted_for_wiki");
        PermissionsSubEvents.push("perm_granted_for_wikipage");
        PermissionsSubEvents.push("perm_granted_for_wikiattachment");
        PermissionsSubEvents.push("perm_granted_for_object");
        return PermissionsSubEvents;
    },
    getProjectSubEventsInArray: function() {
        var ProjectSubEvents = new Array();
        ProjectSubEvents.push("rename_done");
        ProjectSubEvents.push("rename_with_error");
        ProjectSubEvents.push("approved");
        ProjectSubEvents.push("deleted");
        ProjectSubEvents.push("rename_request");
        ProjectSubEvents.push("is_public");
        ProjectSubEvents.push("group_type");
        ProjectSubEvents.push("http_domain");
        ProjectSubEvents.push("unix_box");
        ProjectSubEvents.push("changed_public_info");
        ProjectSubEvents.push("changed_trove");
        ProjectSubEvents.push("membership_request_updated");
        ProjectSubEvents.push("import");
        ProjectSubEvents.push("mass_change");
        return ProjectSubEvents;
    },
    getUserGroupSubEventsInArray: function() {
        var UserGroupSubEvents = new Array();
        UserGroupSubEvents.push("upd_ug");
        UserGroupSubEvents.push("del_ug");
        UserGroupSubEvents.push("changed_member_perm");
        return UserGroupSubEvents;
    },
    getUsersSubEventsInArray: function() {
        var UsersSubEvents = new Array();
        UsersSubEvents.push("changed_personal_email_notif");
        UsersSubEvents.push("added_user");
        UsersSubEvents.push("removed_user");
        return UsersSubEvents;
    },
    getOthersSubEventsInArray: function() {
        var OthersSubEvents = new Array();
        OthersSubEvents.push("changed_bts_form_message");
        OthersSubEvents.push("changed_bts_allow_anon");
        OthersSubEvents.push("changed_patch_mgr_settings");
        OthersSubEvents.push("changed_task_mgr_other_settings");
        OthersSubEvents.push("changed_sr_settings");
        return OthersSubEvents;
    },
    removeAllOptions: function(selectbox) {
        var i;
        for (i = selectbox.options.length-1; i>=0; i--) {
            selectbox.remove(i);
        }
    },
    addOption: function(value, selected, disabled) {
        var optn = Builder.node('option', {'value' : value}, this.sub_events_array[value]);
        $('sub_events_box').appendChild(optn);
        if (selected) {
            optn.selected = true;
        } else {
            optn.selected = false;
        }
        if (disabled) {
            optn.disabled = true;
        } else {
            optn.disabled = false;
        }
    }
});