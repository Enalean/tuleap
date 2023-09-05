/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/* global codendi:readonly jQuery:readonly */

(function ($, window) {
    $(function () {
        $(
            ".artifact-incoming-mail-button, .tracker_artifact_followup_comment_controls_raw_email",
        ).click(function (event) {
            event.stopPropagation();
            event.preventDefault();

            var new_window = window.open("", "_blank");
            new_window.document.write("<pre></pre>");
            $(new_window.document.getElementsByTagName("pre")).text($(this).attr("data-raw-email"));
        });

        $(".tracker-artifact-notification").click(function (event) {
            event.preventDefault();

            var button = $(this);
            var artifact_id = $("#artifact_informations").attr("data-artifact-id");

            $.get(codendi.tracker.base_url + "?aid=" + artifact_id + "&func=manage-subscription", {
                artifact: artifact_id,
            })
                .done(function (data) {
                    codendi.feedback.clear();
                    codendi.feedback.log("info", data.message);

                    updateButtonLabel(button, data.notification);
                })
                .fail(function () {
                    codendi.feedback.clear();
                    codendi.feedback.log(
                        "error",
                        codendi.locales.tracker_artifact.notification_update_error,
                    );
                });

            function updateButtonLabel(button, notification) {
                if (notification === true) {
                    button.attr(
                        "title",
                        codendi.locales.tracker_artifact.disable_notifications_alternate_text,
                    );
                    button.html(
                        '<i class="far fa-bell-slash"></i> ' +
                            codendi.locales.tracker_artifact.disable_notifications,
                    );
                } else {
                    button.attr(
                        "title",
                        codendi.locales.tracker_artifact.enable_notifications_alternate_text,
                    );
                    button.html(
                        '<i class="far fa-bell"></i> ' +
                            codendi.locales.tracker_artifact.enable_notifications,
                    );
                }
            }
        });
    });
})(jQuery, window);
