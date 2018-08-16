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

!(function($) {
    var viewing_ssh_mirror = false;

    $(document).ready(function() {
        selectFirstProtocol();
        $(".plugin_git_transport").click(function() {
            changeCloneURL($(this));
        });
        $(".repo_mirror_option").click(changeMirror);

        toggleContextualHelpForCloneUrl();
        autoSelectGitCloneUrl();
    });

    function selectFirstProtocol() {
        var first = true;
        $(".plugin_git_transport")
            .filter(":visible")
            .each(function() {
                if (first) {
                    $(this).addClass("active git_first_protocol");
                    first = false;
                } else {
                    $(this).removeClass("active git_first_protocol");
                }
            });
        changeCloneURL($(".plugin_git_transport.active"));
    }

    function changeCloneURL(transport_button) {
        var current_protocol = transport_button.attr("data-protocol"),
            current_url = transport_button.attr("data-url");

        if (shouldDisplayReadOnlyLabel()) {
            $("#gitclone_urls_readonly").show();
        } else {
            $("#gitclone_urls_readonly").hide();
        }

        $("#plugin_git_clone_field").val(current_url);
        $(".plugin_git_example_url").html(current_url);

        function shouldDisplayReadOnlyLabel() {
            return viewingSSHMirror() || (gerritIsActive() && !viewingGerrit());
        }

        function viewingGerrit() {
            return current_protocol === "gerrit";
        }

        function viewingSSHMirror() {
            return current_protocol === "ssh" && viewing_ssh_mirror;
        }
    }

    function changeMirror(event) {
        var selected_mirror = $(this),
            ssh_url = selected_mirror.attr("data-ssh-url"),
            name = selected_mirror.attr("data-name");

        markMirrorAsSelected();
        updateUrlData();
        simulateClickOnSSHProtocol();
        event.preventDefault();

        function markMirrorAsSelected() {
            selected_mirror
                .parents("ul")
                .children(".is_selected")
                .removeClass("is_selected");
            selected_mirror.parent().addClass("is_selected");
            $(".current-location-name").html(name);

            viewing_ssh_mirror = selected_mirror.attr("data-is-mirror");
        }

        function updateUrlData() {
            $("#gitclone_urls_protocol_ssh").attr("data-url", ssh_url);
        }

        function simulateClickOnSSHProtocol() {
            $("#gitclone_urls_protocol_ssh").click();
        }
    }

    function gerritIsActive() {
        return $("#plugin_git_is_gerrit_active").val();
    }

    function toggleContextualHelpForCloneUrl() {
        $("#plugin_git_example").hide();
        $("#plugin_git_example-handle").on("click", function() {
            $("#plugin_git_example").toggle();
        });
    }

    function autoSelectGitCloneUrl() {
        $("#plugin_git_clone_field").on("click", function() {
            $(this).select();
        });
    }
})(jQuery);
