/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import jQuery from "jquery";

import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";

(async function ($): Promise<void> {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(
        language,
        "tuleap-hudson_git",
        (locale) =>
            import(
                /* webpackChunkName: "git-administration-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            ),
    );

    function confirmDeletionPopover(): void {
        $(".remove-jenkins-server").each(function () {
            const id = $(this).data("popover-id");

            $(this).popover({
                container: ".git-administration-jenkins-server",
                title: gettext_provider.gettext("Wait a minute..."),
                content: $("#" + id).html(),
            });
        });
    }

    function dismissPopover(): void {
        $(".remove-jenkins-server").popover("hide");
    }

    function bindShowPopover(): void {
        $(".remove-jenkins-server").click(function (event) {
            event.preventDefault();

            dismissPopover();

            $(this).popover("show");
        });
    }

    function testJenkinsServer(url: string): void {
        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        const button = document.querySelector("#jenkins-server-test > .fa") as HTMLButtonElement;

        $.ajax({
            url: "/plugins/hudson_git/test_jenkins_server?jenkins_url_to_test=" + encodeURI(url),
            type: "POST",
            dataType: "json",
            beforeSend: () => {
                button.classList.remove("fa-play");
                button.classList.add("fa-circle-o-notch", "fa-spin");
            },
        }).done(function (data) {
            button.classList.add("fa-play");
            button.classList.remove("fa-circle-o-notch", "fa-spin");

            const test_feedback_element = $("#jenkins-server-test-feedback");

            removeJenkinsFeedback();

            let css_class = "";
            if (data.type === "success") {
                css_class = "text-success";
            } else {
                css_class = "text-error";
            }
            test_feedback_element.append(
                "<span class='" + css_class + "'>" + data.message + "</span>",
            );
        });
    }

    function removeJenkinsFeedback(): void {
        const feedback = document.querySelector("#jenkins-server-test-feedback > span");

        if (feedback) {
            feedback.remove();
        }
    }

    $(function (): void {
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

        const jenkins_url = document.getElementById("jenkins-server-url");
        if (!(jenkins_url instanceof HTMLInputElement)) {
            throw new Error("#jenkins-server-url not found or is not an input");
        }

        const test_button = document.getElementById("jenkins-server-test");
        if (!(test_button instanceof HTMLButtonElement)) {
            throw new Error("#jenkins-server-test not found or is not a button");
        }

        jenkins_url.addEventListener("keyup", () => {
            removeJenkinsFeedback();

            if (jenkins_url.checkValidity()) {
                test_button.disabled = false;
            } else {
                test_button.disabled = true;
            }
        });

        test_button.addEventListener("click", () => {
            if (jenkins_url.checkValidity()) {
                testJenkinsServer(jenkins_url.value);
            }
        });
    });
})(jQuery);
