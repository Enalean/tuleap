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

export const PROJECT_BANNER_MESSAGE_CLOSE_BUTTON_ID = "project-banner-close";
export const PROJECT_BANNER_VISIBLE_GLOBAL_CLASS = "has-visible-project-banner";
export const PROJECT_BANNER_HIDDEN_CLASS = "project-banner-hidden";

export function allowToHideAndShowProjectBanner(
    mount_point: Document,
    tlpPatch: (url: string, init: RequestInit & { method?: "PATCH" }) => Promise<Response>,
): void {
    const project_banner_message_close_button = mount_point.getElementById(
        PROJECT_BANNER_MESSAGE_CLOSE_BUTTON_ID,
    );

    if (project_banner_message_close_button === null) {
        return;
    }

    const full_project_banner = project_banner_message_close_button.parentElement;
    if (full_project_banner === null) {
        throw new Error(
            "Project banner close button is supposed to be contained in the project banner",
        );
    }

    for (const sidebar of mount_point.getElementsByTagName("tuleap-project-sidebar")) {
        sidebar.addEventListener("show-project-announcement", (event) => {
            toggleProjectBannerMessage(event, mount_point.body, full_project_banner);
        });
    }
    const project_id = project_banner_message_close_button.dataset.projectId;
    const user_id = project_banner_message_close_button.dataset.userId;
    if (project_id === undefined || user_id === undefined) {
        throw new Error(
            "Project banner close button is supposed to have information about the current user and project",
        );
    }
    project_banner_message_close_button.addEventListener("click", async (): Promise<void> => {
        await hideProjectBannerMessage(
            tlpPatch,
            mount_point.body,
            full_project_banner,
            Number.parseInt(project_id, 10),
            Number.parseInt(user_id, 10),
        );
    });
}

function toggleProjectBannerMessage(
    event: Event,
    document_body: HTMLElement,
    full_project_banner: HTMLElement,
): void {
    event.preventDefault();
    window.scrollTo(0, 0);
    document_body.classList.add(PROJECT_BANNER_VISIBLE_GLOBAL_CLASS);
    full_project_banner.classList.remove(PROJECT_BANNER_HIDDEN_CLASS);
}

async function hideProjectBannerMessage(
    tlpPatch: (url: string, init: RequestInit & { method?: "PATCH" }) => Promise<Response>,
    document_body: HTMLElement,
    full_project_banner: HTMLElement,
    project_id: number,
    user_id: number,
): Promise<void> {
    document_body.classList.remove(PROJECT_BANNER_VISIBLE_GLOBAL_CLASS);
    full_project_banner.classList.add(PROJECT_BANNER_HIDDEN_CLASS);

    // Not dealing with potential errors here, worst case scenario the user will have to close the banner again on the next page
    await tlpPatch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ key: `project_banner_${project_id}`, value: "hidden" }),
    });
}
