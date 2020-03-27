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

/* global jQuery:readonly codendi:readonly tuleap:readonly */

(function ($) {
    function initNatureColumnEditor(cog) {
        $(cog).click(function (evt) {
            evt.preventDefault();
        });

        $(cog).popover({
            container: "body",
            html: true,
            title: codendi.getText("nature_column_editor", "title"),
            content: getPopoverContent($(cog)),
        });
    }

    function getPopoverContent(cog) {
        var th_parent = cog.parents(".tracker_report_table_column"),
            func = "renderer",
            renderer_id = $("#tracker_report_renderer_current").attr("data-renderer-id"),
            report_id = $("#tracker-report-normal-query").attr("data-report-id"),
            column_id = th_parent.attr("data-column-id"),
            format = th_parent.attr("data-field-artlink-nature-format"),
            content = '<div class="nature-column-popover">';

        if (!format) {
            format = "";
        }

        content += '<form action="#" method="get" class="save-nature-column-format">';

        content +=
            '<p class="alert alert-danger">' +
            codendi.getText("nature_column_editor", "something_went_wrong") +
            "</p>";
        content += "<p>" + codendi.getText("nature_column_editor", "how_to") + "</p>";
        content += "<p>" + codendi.getText("nature_column_editor", "supported_types") + "</p>";

        content += '<input type="hidden" name="renderer" value="' + renderer_id + '">';
        content += '<input type="hidden" name="report" value="' + report_id + '">';
        content += '<input type="hidden" name="func" value="' + func + '">';

        content +=
            '<label for="nature-column-editor-format">' +
            codendi.getText("nature_column_editor", "column_format_label") +
            "</label>";
        content +=
            '<input type="text" id="nature-column-editor-format" name="renderer_table[configure-column][' +
            column_id +
            ']" ';
        content +=
            'placeholder="' +
            codendi.getText("nature_column_editor", "column_format_placeholder") +
            '" ';
        content += 'value="' + tuleap.escaper.html(format) + '"';
        content += '">';

        content += '<div class="nature-column-popover-actions">';
        content +=
            '<button type="button" class="btn cancel-nature-column-editor">' +
            codendi.getText("nature_column_editor", "cancel") +
            "</button>";
        content +=
            '<button type="submit" class="btn btn-primary">' +
            codendi.getText("nature_column_editor", "save") +
            "</button>";
        content += "</div>";

        content += "</form>";
        content += "</div>";

        return content;
    }

    function cancelNatureColumnEditor() {
        $(".nature-column-editor").popover("hide");
    }

    function saveNatureColumnFormat(event) {
        event.preventDefault();

        var form = $(this);

        $.get(codendi.tracker.base_url, form.serialize())
            .done(function () {
                location.reload();
            })
            .fail(function () {
                $(".popover.in .alert-danger").show(0);
            });
    }

    $(function () {
        codendi.tracker.report.table.initNatureColumnEditor = initNatureColumnEditor;

        $(".nature-column-editor").each(function () {
            initNatureColumnEditor($(this));
        });

        $("body").on("click", function (event) {
            if (
                $(event.target).parents(".nature-column-editor").length === 0 &&
                $(event.target).parents(".popover.in").length === 0
            ) {
                cancelNatureColumnEditor();
                return;
            }

            if ($(event.target).parents(".nature-column-editor").length === 1) {
                var clicked = $(event.target).parents(".nature-column-editor")[0];
                $(".nature-column-editor").each(function (index, element) {
                    if (element !== clicked) {
                        $(element).popover("hide");
                    }
                });
            }
        });

        $("body").on("click", ".cancel-nature-column-editor", cancelNatureColumnEditor);
        $("body").on("submit", ".save-nature-column-format", saveNatureColumnFormat);
    });
})(jQuery);
