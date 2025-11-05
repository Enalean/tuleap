/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { postJSON, uri } from "@tuleap/fetch-result";
import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";

import "./jenkins-administration.scss";

document.addEventListener("DOMContentLoaded", () => {
    openAllTargetModalsOnClick(document, ".jenkins-modal-trigger");

    const jenkins_url = document.getElementById("jenkins-server-url");
    if (!(jenkins_url instanceof HTMLInputElement)) {
        return;
    }

    const test_button = document.getElementById("jenkins-server-test");
    if (!(test_button instanceof HTMLButtonElement)) {
        return;
    }

    jenkins_url.addEventListener("keyup", () => {
        removeJenkinsFeedback();

        test_button.disabled = !jenkins_url.checkValidity();
    });

    test_button.addEventListener("click", () => {
        if (jenkins_url.checkValidity()) {
            testJenkinsServer(jenkins_url.value);
        }
    });

    function removeJenkinsFeedback(): void {
        const feedback = document.querySelector("#jenkins-server-test-feedback > span");

        if (feedback) {
            feedback.remove();
        }
    }

    function testJenkinsServer(url: string): void {
        const icon = document.getElementById("jenkins-server-test-icon");

        icon?.classList.remove("fa-play");
        icon?.classList.add("fa-circle-notch", "fa-spin");

        postJSON<{ type: "success" | "error"; message: string }>(
            uri`/plugins/hudson_git/test_jenkins_server?jenkins_url_to_test=${url}`,
            {},
        ).map(function (data): void {
            icon?.classList.add("fa-play");
            icon?.classList.remove("fa-circle-notch", "fa-spin");

            const test_feedback_element = document.getElementById("jenkins-server-test-feedback");

            removeJenkinsFeedback();

            const feedback = document.createElement("span");
            feedback.classList.add(
                data.type === "success" ? "tlp-text-success" : "tlp-text-danger",
            );
            feedback.textContent = data.message;

            test_feedback_element?.append(feedback);
        });
    }
});
