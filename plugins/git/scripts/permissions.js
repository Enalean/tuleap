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

/* global jQuery:readonly codendi:readonly */

(function ($) {
    function bindAddPermission() {
        $(".add-fine-grained-permission").click(function (event) {
            event.preventDefault();

            $(this).blur();

            var type = $(this).attr("data-type"),
                regexp = $(this).attr("data-regexp-enabled"),
                table = $(".git-fine-grained-permissions-" + type),
                tbody = table.children("tbody"),
                permission_template = $("#add-fine-grained-permission-template"),
                index = getNewIndex(type);

            var new_row = "";
            var input_tag =
                '<input type="text" name="add-' +
                type +
                "-name[" +
                index +
                ']" placeholder="' +
                codendi.getText("git", "add_" + type + "_permission_placeholder") +
                '">';
            var label_regexp_enabled =
                '<p class="text-info">' +
                codendi.getText("git", "regexp_permission_enabled_info") +
                "</p>";
            var write_permission_tag = permission_template
                .clone()
                .removeAttr("id")
                .attr("name", "add-" + type + "-write[" + index + "][]")[0].outerHTML;
            var rewind_permission_tag = permission_template
                .clone()
                .removeAttr("id")
                .attr("name", "add-" + type + "-rewind[" + index + "][]")[0].outerHTML;

            new_row += "<tr>";

            new_row += "<td>" + input_tag;
            if (regexp) {
                new_row += label_regexp_enabled;
            }
            ("</td>");
            new_row += "<td>" + write_permission_tag + "</td>";
            new_row += "<td>" + rewind_permission_tag + "</td>";
            new_row += "<td/>";
            new_row += "</tr>";

            tbody.append($(new_row));
        });
    }

    function bindToggleFineGrainedPermissions() {
        $(".toggle-fine-grained-permissions").change(function () {
            $(".plugin_git_write_permissions_select, .plugin_git_rewind_permissions_select")
                .prop("disabled", function (index, value) {
                    return !value;
                })
                .filter(function () {
                    if (isViewingDefaultAccessControlAdmin()) {
                        return $(this).hasClass("plugin_git_write_permissions_select");
                    }

                    return true;
                })
                .prop("required", function (index, value) {
                    return !value;
                });
        });
    }

    function isViewingDefaultAccessControlAdmin() {
        return /action=admin-default-/.test(location.search);
    }

    function getNewIndex(type) {
        return $('input[name^="add-' + type + '-name"]').length;
    }

    function confirmDeletionPopover() {
        $(".remove-fine-grained-permission").each(function () {
            var id = $(this).data("popover-id");
            var form_action = $(this).data("form-action");

            $(this).popover({
                container: ".git-per-tags-branches-permissions",
                title: codendi.getText("git", "remove_webhook_title"),
                content:
                    '<form method="POST" action="' +
                    form_action +
                    '">' +
                    $("#" + id).html() +
                    "</form>",
            });

            $("#" + id).remove();
        });
    }

    function dismissPopover() {
        $(".remove-fine-grained-permission").popover("hide");
    }

    function bindShowPopover() {
        $(".remove-fine-grained-permission").click(function (event) {
            event.preventDefault();

            dismissPopover();

            $(this).popover("show");
        });
    }

    function bindToggleEnableRegexp() {
        $("#use-fine-grained-permissions").change(function () {
            if ($(this).is(":checked")) {
                $(".regexp_permission_activated").show();
            } else {
                $(".regexp_permission_activated").hide();
            }
        });
    }

    function bindToogleModalWarningDisableRegexp() {
        var already_check_modal = false;

        $(".save-permissions-with-regexp").click(function (event) {
            if (already_check_modal === true) {
                return;
            }

            if (
                !$(".use-regexp").is(":checked") &&
                $(".save-permissions-with-regexp").attr("data-are-regexp-enabled") == 1
            ) {
                event.preventDefault();
                $("#modal-regexp-delete").modal("toggle");
            } else if (
                $(".use-regexp").is(":checked") &&
                $(".save-permissions-with-regexp").attr("data-are-regexp-confliting") == 1
            ) {
                event.preventDefault();
                $("#modal-regexp-delete").modal("toggle");
                $(".use-regexp").attr("checked", false);
            } else {
                already_check_modal = true;
            }
        });

        $(".dismiss-popover").click(function () {
            $("#modal-regexp-delete").modal("toggle");
        });
    }

    $(function () {
        bindAddPermission();
        bindToggleFineGrainedPermissions();
        bindToggleEnableRegexp();

        bindToogleModalWarningDisableRegexp();

        confirmDeletionPopover();

        bindShowPopover();

        $("body").on("click", function (event) {
            if ($(event.target).hasClass("dismiss-popover")) {
                dismissPopover();
            }

            if (
                $(event.target).data("toggle") !== "popover" &&
                $(event.target).parents(".popover.in").length === 0 &&
                $(event.target).parents('[data-toggle="popover"]').length === 0
            ) {
                dismissPopover();
            }
        });
    });
})(jQuery);
