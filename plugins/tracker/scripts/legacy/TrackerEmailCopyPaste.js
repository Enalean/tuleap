/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

/* global codendi:readonly jQuery:readonly */

(function ($, window, document) {
    var popovers = [];

    /**
     * Return true if the copy is supported by the current browser
     *
     * This must be called in a user-intitited call stack else it returns false for security reasons
     */
    function isCopyToClipboardSupported() {
        var supported = document.queryCommandSupported("copy");
        if (supported) {
            // Firefox before 41 always return true for queryCommandSupported('copy'), so double check
            try {
                document.execCommand("copy");
            } catch (e) {
                supported = false;
            }
        }

        return supported;
    }

    function clearSelection() {
        var selection = window.getSelection ? window.getSelection() : document.selection;
        if (!selection) {
            return;
        }

        if (selection.removeAllRanges) {
            selection.removeAllRanges();
        } else if (selection.empty) {
            selection.empty();
        }
    }

    function initTrackerEmail() {
        var title,
            description,
            envelope = $(this);

        title = codendi.getText(
            "tracker_email",
            envelope.hasClass("email-tracker-reply") ? "title_reply" : "title"
        );
        description = codendi.getText(
            "tracker_email",
            envelope.hasClass("email-tracker-reply") ? "description_reply" : "description"
        );

        envelope.popover({
            placement: "right",
            html: true,
            trigger: "click",
            container: "#submit-new-by-mail-popover-container",
            title: title,
            content: buildPopoverContent,
        });

        popovers.push(envelope);

        function buildPopoverContent() {
            var content = $("<div><p>" + description + "</p></div>"),
                email = envelope.data("email");

            if (isCopyToClipboardSupported()) {
                addCopyButtonToContent(content, email);
            } else {
                var link = $(
                    '<a href="mailto:' + email + '" title="' + email + '">' + email + "</a>"
                );

                content.append($('<p class="submit-new-by-mail-copy-fallback"></p>').append(link));
            }

            return content;
        }

        function addCopyButtonToContent(content, email) {
            var input = $('<input type="text" value="' + email + '" readonly />'),
                button = $('<button class="btn"><i class="fa fa-files-o"></i></button>'),
                copied = $(
                    '<p class="text-info">' + codendi.getText("tracker_email", "copied") + "</p>"
                ),
                container = $(
                    '<div class="input-append" id="tracker-email-copy-to-clipboard"></div>'
                )
                    .append(input)
                    .append(button);

            button;

            input.click(function () {
                $(this).select();
            });

            content.append(container).append(copied);

            button
                .tooltip({
                    title: codendi.getText("tracker_email", "copy"),
                    placement: "bottom",
                })
                .click(function (evt) {
                    evt.preventDefault();
                    input.select();
                    document.execCommand("copy");
                    clearSelection();
                    copied.addClass("copied");
                });
        }
    }

    $(function () {
        $(".email-tracker").each(initTrackerEmail);

        $("body").click(function (event) {
            if (
                $(event.target).parents(".email-tracker").length === 0 &&
                $(event.target).parents(".popover.in").length === 0
            ) {
                popovers.forEach(function (popover) {
                    popover.popover("hide");
                });
                return;
            }

            if ($(event.target).parents(".email-tracker").length === 1) {
                var clicked = $(event.target).parents(".email-tracker")[0];
                $(".email-tracker").each(function (index, element) {
                    if (element !== clicked) {
                        $(element).popover("hide");
                    }
                });
            }
        });
    });
})(jQuery, window, document);
