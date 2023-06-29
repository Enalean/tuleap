/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { selectOrThrow } from "@tuleap/dom";
import {
    createModal,
    EVENT_TLP_MODAL_HIDDEN,
    EVENT_TLP_MODAL_SHOWN,
    EVENT_TLP_MODAL_WILL_HIDE,
    openAllTargetModalsOnClick,
    openTargetModalIdOnClick,
    openModalAndReplacePlaceholders,
} from "@tuleap/tlp-modal";

const initSimpleModals = (example) => {
    if (example.id === "example-modals-usage") {
        openAllTargetModalsOnClick(document, "[data-modal-button]");
        return;
    }
    if (example.id === "example-modals-types") {
        openAllTargetModalsOnClick(document, "[data-modal-types-button]");
    }
};

function logToConsoleToHelpDeveloperUnderstandEvents(message) {
    // eslint-disable-next-line no-console
    console.info(message);
}

const initEventsModal = (example) => {
    if (example.id !== "example-modals-usage") {
        return;
    }
    const events_modal_trigger = selectOrThrow(document, "#modal-button-events");
    const events_modal_element = selectOrThrow(document, "#events-modal");
    const events_modal_submit = selectOrThrow(document, "#events-modal-submit");
    const events_modal = createModal(events_modal_element);
    events_modal.addEventListener(EVENT_TLP_MODAL_SHOWN, () => {
        logToConsoleToHelpDeveloperUnderstandEvents("Events modal is shown");
    });
    events_modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
        logToConsoleToHelpDeveloperUnderstandEvents("Events modal is hidden");
    });
    events_modal.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
        logToConsoleToHelpDeveloperUnderstandEvents("Events modal is going to hide");
        event.preventDefault();
        // Use confirm() for example purpose
        // eslint-disable-next-line no-alert
        const should_hide = window.confirm("You may lose your work. Close the modal?");
        if (should_hide) {
            events_modal.hide();
        }
    });
    events_modal_trigger.addEventListener("click", () => {
        events_modal.show();
    });
    events_modal_submit.addEventListener("click", () => {
        events_modal.hide();
    });
};

const initTargetModals = (example) => {
    if (example.id !== "example-modals-open-target") {
        return;
    }
    const DELETE_BUTTON_ID = "delete-button";
    const EDIT_BUTTONS_SELECTOR = "[data-edit-app-button]";
    openTargetModalIdOnClick(document, DELETE_BUTTON_ID);
    openAllTargetModalsOnClick(document, EDIT_BUTTONS_SELECTOR);
};

const initReplacePlaceholderModals = (example) => {
    if (example.id !== "example-modals-replace-placeholders") {
        return;
    }
    const hiddenInputReplaceCallback = (clicked_button) => {
        if (!clicked_button.dataset.appId) {
            throw new Error("Missing data-app-id attribute on button");
        }
        return clicked_button.dataset.appId;
    };

    const paragraphReplaceCallback = (clicked_button) => {
        if (!clicked_button.dataset.appName) {
            throw new Error("Missing data-app-name attribute on button");
        }
        return `You are about to delete ${clicked_button.dataset.appName}. Please, confirm your action.`;
    };

    const DELETE_BUTTONS_SELECTOR = "[data-delete-app-button]";
    const DELETE_APP_MODAL_ID = "delete-app-modal";
    const DELETE_MODAL_HIDDEN_INPUT_ID = "delete-modal-app-id";
    const DELETE_MODAL_DESCRIPTION = "delete-modal-app-name";

    openModalAndReplacePlaceholders({
        document,
        buttons_selector: DELETE_BUTTONS_SELECTOR,
        modal_element_id: DELETE_APP_MODAL_ID,
        hidden_input_replacement: {
            input_id: DELETE_MODAL_HIDDEN_INPUT_ID,
            hiddenInputReplaceCallback,
        },
        paragraph_replacement: {
            paragraph_id: DELETE_MODAL_DESCRIPTION,
            paragraphReplaceCallback,
        },
    });
};

export const initModals = (example) => {
    initSimpleModals(example);
    initEventsModal(example);
    initTargetModals(example);
    initReplacePlaceholderModals(example);
};
