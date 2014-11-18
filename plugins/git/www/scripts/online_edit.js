/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
!function($) {

    function getEditForm() {
        var file_name = getParameterByName('f');
        var edit_form = '<div class="online_commit">' +
            '<p>' +
                '<div id="editor">' +
                    '<textarea id="editor_textarea" name="editor"/>' +
                '</div>' +
            '</p>' +
            '<form id="commit_form" onsubmit="return false">' +
                '<p>' +
                    '<label for="message_commit">' + gettext('commit_message') + '</label>' +
                    '<input id="message_commit" class="input-xxlarge" type="text" name="commit_message" required ="" value="' + gettext('commit_message_value') + file_name + '" />' +
                '</p>' +
                '<p>' +
                    '<textarea id="description_commit" class="input-xxlarge" name="description_commit" placeholder="' + gettext('commit_description_value') + '" />' +
                '</p>' +
                '<p>' +
                    '<input class="btn" id="cancel" type="button" value="' + gettext('cancel_button_value') + '" />' +
                    '<input class="btn btn-primary" id="commit" type="submit" value="' + gettext('commit_button_value') + '" >' +
                '</p>' +
            '</form>' +
        '</div>';

        return edit_form;
    }

    function isABlobPage() {
        return getParameterByName('a') === 'blob';
    }

    function getParameterByName(name) {
        var regex = new RegExp(name + "=([^&#=]*)");
        var name_value_array = regex.exec(window.location.search);
        if(name_value_array !== null) {
            return decodeURIComponent(name_value_array[1]);
        }

        return false;
    }

    function createEditButtonAndCommitForm() {
        if (hasUserRightAndGitPluginConfig() && isTopOfCurrentBranch()) {
            displayEditbuttonAndFormIfOnlineEditAllowed();
        } else if (hasUserRightAndGitPluginConfig()) {
            displayEditbuttonIfIsNotTopOfBranch();
        }
    }

    function displayEditbuttonAndFormIfOnlineEditAllowed() {
        var edit_button = '<input id="edit" class="btn" type="button" value="' + gettext('edit_button_value') + '" >';
        $("#gitphp .page_path").append(edit_button);
        $("#gitphp").append(getEditForm());
        $(".online_commit").hide();
        $("#message_commit").focus(function(){
            this.select();
        });
        edit_button = $("#edit");
        setEditButtonClickEvent(edit_button);
        setCancelButtonClickEvent(edit_button);
    }

    function displayEditbuttonIfIsNotTopOfBranch() {
        var edit_button = '<input id="edit" class="btn" type="button" data-toggle="tooltip" title="' + gettext('not_top_branch') + '" value="' + gettext('edit_button_value') + '" >';
        $("#gitphp .page_path").append(edit_button);
        edit_button = $("#edit");
        edit_button.attr("disabled", "disabled");
        edit_button.tooltip({ placement: "right"});
        edit_button.tooltip("toggle");
    }

    function hasUserRightAndGitPluginConfig() {
        return $("#plugin_git_onlinemodif").hasClass("editable");
    }

    function isTopOfCurrentBranch() {
        return $("#gitphp div.title span.refs span.head a ").length > 0;
    }

    function setEditButtonClickEvent(edit_button) {
        edit_button.click(function (event) {
            if ($(this).attr("value") === gettext('edit_button_value')) {
                showEditForm();
                $(this).attr("value", gettext('cancel_button_value'));
            } else {
                hideEditForm();
                $(this).attr("value", gettext('edit_button_value'));
            }
            $(this).attr("class", "btn");
        });
    }

    function setCancelButtonClickEvent(edit_button) {
        $("input[type=button]#cancel").click(function (event) {
            hideEditForm();
            edit_button.attr("value", gettext('edit_button_value'));
        });
    }

    function showEditForm() {
        $("#gitphp .page_body").hide();
        $("#gitphp .page_footer").hide();
        $(".online_commit").show();
    }

    function hideEditForm() {
        $("#gitphp .page_body").show();
        $("#gitphp .page_footer").show();
        $(".online_commit").hide();
    }

    function initOnlineEdit() {
        if (isABlobPage()) {
            createEditButtonAndCommitForm();
        }
    }

    function gettext(key) {
        return codendi.getText('git', key);
    }

    $(document).ready(initOnlineEdit);
}(window.jQuery);