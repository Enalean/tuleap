/*
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

import { addFeedback } from "@tuleap/fp-feedback";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

document.addEventListener("DOMContentLoaded", () => {
    const user_locale = document.body.dataset.userLocale;
    if (!user_locale) {
        throw new Error("No user locale");
    }

    const git_create_branch_link = document.getElementById("artifact-create-git-branches");
    const action_dropdown_icon = document.getElementById("tracker-artifact-action-icon");

    if (!git_create_branch_link || !action_dropdown_icon) {
        return;
    }

    git_create_branch_link.addEventListener("click", async () => {
        if (git_create_branch_link.classList.contains("disabled")) {
            return;
        }

        action_dropdown_icon.classList.add("fa-spin", "fa-spinner");
        git_create_branch_link.classList.add("disabled");

        const loading_modal_element = document.createElement("div");
        loading_modal_element.classList.add("tuleap-modal-loading");
        document.body.appendChild(loading_modal_element);

        const modal_mount_point = document.getElementById("tracker-modal-actions");
        if (modal_mount_point === null) {
            throw new Error("Cannot find the mount point for the tracker actions modal");
        }

        const gettext_provider = await initVueGettext(
            createGettext,
            (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
        );

        try {
            const { init } = await import(
                /* webpackChunkName: "create-git-branch-modal" */ "./modal"
            );

            await init(git_create_branch_link, modal_mount_point, gettext_provider);
        } catch (e) {
            addFeedback(
                "error",
                gettext_provider.$gettext("Error while loading the Git branch creation modal."),
            );
            throw e;
        } finally {
            document.body.removeChild(loading_modal_element);
            action_dropdown_icon.classList.remove("fa-spin", "fa-spinner");
            git_create_branch_link.classList.remove("disabled");
        }
    });
});
