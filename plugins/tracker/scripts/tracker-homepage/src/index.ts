/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { createPopover } from "tlp";
import { openModalAndReplacePlaceholders } from "../../../../../src/scripts/tuleap/modals/modal-opener";
import {
    getPOFileFromLocale,
    initGettext,
} from "../../../../../src/scripts/tuleap/gettext/gettext-init";
import {
    buildDeletionDescriptionCallback,
    replaceTrackerIDCallback,
} from "./replacers-modal-delete";

document.addEventListener(
    "DOMContentLoaded",
    async (): Promise<void> => {
        handleTrackerStatisticsPopovers();
        await handleTrackerDeletion();
    }
);

function handleTrackerStatisticsPopovers(): void {
    for (const trigger of document.querySelectorAll(".trackers-homepage-tracker")) {
        if (!(trigger instanceof HTMLElement)) {
            continue;
        }

        const popover_content = document.getElementById(
            "tracker-statistics-popover-" + trigger.dataset.trackerId
        );
        if (popover_content === null) {
            throw new Error(
                `Statistics popover not found for tracker #${trigger.dataset.trackerId}`
            );
        }

        createPopover(trigger, popover_content, {
            placement: "right",
        });
    }
}

async function handleTrackerDeletion(): Promise<void> {
    for (const trash of document.querySelectorAll(".trackers-homepage-tracker-trash")) {
        trash.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
        });
    }

    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(
        language,
        "tuleap-tracker",
        (locale) =>
            import(
                /* webpackChunkName: "tracker-homepage-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            )
    );

    openModalAndReplacePlaceholders({
        document: document,
        buttons_selector: ".trackers-homepage-tracker-trash",
        modal_element_id: "tracker-homepage-delete-modal",
        hidden_input_replacement: {
            input_id: "tracker-homepage-delete-modal-tracker-id",
            hiddenInputReplaceCallback: replaceTrackerIDCallback,
        },
        paragraph_replacement: {
            paragraph_id: "tracker-homepage-delete-modal-tracker-name",
            paragraphReplaceCallback: buildDeletionDescriptionCallback(gettext_provider),
        },
    });
}
