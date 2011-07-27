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
    initialize: function () {
        var title = $('history_search_title');
        title.observe('click', this.toggleForm);
        // We may make the form hidden by default
        //$('project_history_search').hide();
        Event.observe($('events'), 'change', this.SelectSubEvent.bindAsEventListener(this)); 
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
    SelectSubEvent: function() {
        this.removeAllOptions(document.project_history_form.SubEvent);
        this.addOption(document.project_history_form.SubEvent, "Any");

        //Permission section
        if(document.project_history_form.events.value == "Permissions"){
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_field");
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_tracker");  
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_package"); 
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_release");  
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_document"); 
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_folder");   
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_docgroup"); 
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_wiki"); 
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_wikipage"); 
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_wikiattachment");   
            this.addOption(document.project_history_form.SubEvent,"perm_reset_for_object");
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_field");
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_tracker");  
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_package"); 
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_release");  
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_document"); 
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_folder");   
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_docgroup"); 
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_wiki"); 
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_wikipage"); 
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_wikiattachment");
            this.addOption(document.project_history_form.SubEvent,"perm_granted_for_object", "");
        }

        //Project section
        if(document.project_history_form.events.value == "Project"){
            this.addOption(document.project_history_form.SubEvent,"rename_done");
            this.addOption(document.project_history_form.SubEvent,"rename_with_error");
            this.addOption(docment.project_history_form.SubEvent,"approved");
            this.addOption(document.project_history_form.SubEvent,"deleted");
            this.addOption(document.project_history_form.SubEvent,"rename_request");
            this.addOption(document.project_history_form.SubEvent,"status");
            this.addOption(document.project_history_form.SubEvent,"is_public");
            this.addOption(document.project_history_form.SubEvent,"group_type");
            this.addOption(document.project_history_form.SubEvent,"http_domain");
            this.addOption(document.project_history_form.SubEvent,"unix_box");
            this.addOption(document.project_history_form.SubEvent,"changed_public_info");
            this.addOption(document.project_history_form.SubEvent,"changed_trove");
            this.addOption(document.project_history_form.SubEvent,"membership_request_updated");
            this.addOption(document.project_history_form.SubEvent,"import");
            this.addOption(document.project_history_form.SubEvent,"mass_change");
        }

        //User group section
        if(document.project_history_form.events.value == "User Group"){
            this.addOption(document.project_history_form.SubEvent,"upd_ug");
            this.addOption(document.project_history_form.SubEvent,"del_ug");
            this.addOption(document.project_history_form.SubEvent,"changed_member_perm", "");
        }

        //Users section
        if(document.project_history_form.events.value == "Users"){
            this.addOption(document.project_history_form.SubEvent,"changed_personal_email_notif");
            this.addOption(document.project_history_form.SubEvent,"added_user");
            this.addOption(document.project_history_form.SubEvent,"removed_user");
        }

        //Uncatogorised items section
        if(document.project_history_form.events.value == "Others"){
            this.addOption(document.project_history_form.SubEvent,"changed_bts_form_message");
            this.addOption(document.project_history_form.SubEvent,"changed_bts_allow_anon");
            this.addOption(document.project_history_form.SubEvent,"changed_patch_mgr_settings");
            this.addOption(document.project_history_form.SubEvent,"changed_task_mgr_other_settings");
            this.addOption(document.project_history_form.SubEvent,"changed_sr_settings");
        }
    },
    removeAllOptions: function(selectbox) {
        var i;
        for(i = selectbox.options.length-1; i>=0; i--) {
            selectbox.remove(i);
        }
    },
    addOption: function(selectbox, value) {
        var optn = document.createElement("option");
        //Need to find how to use the value as index in the tab file
        //optn.text = <?php echo $GLOBALS["Language"]->getText("project_admin_utils", $value);?>;
        optn.text = value;
        optn.value = value;
        selectbox.options.add(optn);
    }
});