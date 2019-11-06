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

import { patch } from "../../../../../../src/www/themes/common/tlp/src/js/fetch-wrapper";
import { initGettext } from "../../../../../../src/www/scripts/tuleap/gettext/gettext-init";

document.addEventListener("DOMContentLoaded", () => {
    const action_button = document.getElementById("artifact-explicit-backlog-action");
    if (action_button === null) {
        return;
    }

    let action = action_button.dataset.action;
    const project_id = action_button.dataset.projectId;
    const artifact_id = action_button.dataset.artifactId;

    if (project_id === undefined || artifact_id === undefined) {
        throw new Error(
            "Action button is supposed to have information about the current artifact and project."
        );
    }

    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    action_button.addEventListener(
        "click",
        async (): Promise<void> => {
            const gettext_provider = await initGettext(
                language,
                "artifact-additional-action",
                locale =>
                    import(/* webpackChunkName: "artifact-additional-action-po-" */ `../po/${locale}.po`)
            );

            if (action === "add") {
                await patch(`/api/v1/projects/${encodeURIComponent(project_id)}/backlog`, {
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        add: [{ id: Number.parseInt(artifact_id, 10) }]
                    })
                });
                const title = action_button.getElementsByClassName(
                    "additional-artifact-action-title"
                )[0];
                title.textContent = gettext_provider.gettext("Remove from top backlog");
                // eslint-disable-next-line require-atomic-updates
                action = "remove";
            } else if (action === "remove") {
                await patch(`/api/v1/projects/${encodeURIComponent(project_id)}/backlog`, {
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        remove: [{ id: Number.parseInt(artifact_id, 10) }]
                    })
                });
                const title = action_button.getElementsByClassName(
                    "additional-artifact-action-title"
                )[0];
                title.textContent = gettext_provider.gettext("Add to top backlog");
                // eslint-disable-next-line require-atomic-updates
                action = "add";
            }
        }
    );
});
