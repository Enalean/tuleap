/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { patch } from "@tuleap/tlp-fetch";
import { getPOFileFromLocaleWithoutExtension, initGettext } from "@tuleap/gettext";
import { addFeedback, clearAllFeedbacks } from "@tuleap/fp-feedback";

export function initArtifactAdditionalAction(mount_point: Document): void {
    const action_button = mount_point.getElementById("artifact-explicit-backlog-action");
    if (action_button === null) {
        return;
    }

    let action = action_button.dataset.action;
    const project_id = action_button.dataset.projectId;
    const artifact_id = action_button.dataset.artifactId;

    if (project_id === undefined || artifact_id === undefined) {
        throw new Error(
            "Action button is supposed to have information about the current artifact and project.",
        );
    }

    const language = mount_point.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const action_button_wrapper = action_button.parentElement;
    const action_button_icon = action_button.querySelector("i");
    const action_button_title = action_button
        .getElementsByClassName("additional-artifact-action-title")
        .item(0);
    if (
        action_button_wrapper === null ||
        action_button_title === null ||
        action_button_icon === null
    ) {
        throw new Error("Cannot find button icon, title or parent element");
    }

    action_button.addEventListener("click", async (): Promise<void> => {
        if (action_button_wrapper.classList.contains("disabled")) {
            return;
        }
        const gettext_provider = await initGettext(
            language,
            "artifact-additional-action",
            (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
        );

        clearAllFeedbacks();
        if (action === "add") {
            try {
                action_button_wrapper.classList.add("disabled");
                await patch(`/api/v1/projects/${encodeURIComponent(project_id)}/backlog`, {
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        add: [{ id: Number.parseInt(artifact_id, 10) }],
                    }),
                });
            } catch (e) {
                addFeedback(
                    "error",
                    gettext_provider.gettext(
                        "An error occurred while adding this artifact to top backlog.",
                    ),
                );

                return;
            } finally {
                action_button_wrapper.classList.remove("disabled");
            }

            addFeedback(
                "info",
                gettext_provider.gettext("This artifact has been added to top backlog."),
            );
            action_button_icon.classList.remove("fa-tlp-add-to-backlog");
            action_button_icon.classList.add("fa-tlp-remove-from-backlog");
            action_button_title.textContent = gettext_provider.gettext("Remove from top backlog");
            action = "remove";
        } else if (action === "remove") {
            action_button_wrapper.classList.add("disabled");
            try {
                await patch(`/api/v1/projects/${encodeURIComponent(project_id)}/backlog`, {
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        remove: [{ id: Number.parseInt(artifact_id, 10) }],
                    }),
                });
            } catch (e) {
                addFeedback(
                    "error",
                    gettext_provider.gettext(
                        "An error occurred while removing this artifact from top backlog.",
                    ),
                );

                return;
            } finally {
                action_button_wrapper.classList.remove("disabled");
            }
            addFeedback(
                "info",
                gettext_provider.gettext("This artifact has been removed from top backlog."),
            );
            action_button_icon.classList.remove("fa-tlp-remove-from-backlog");
            action_button_icon.classList.add("fa-tlp-add-to-backlog");
            action_button_title.textContent = gettext_provider.gettext("Add to top backlog");
            action = "add";
        }
    });
}
