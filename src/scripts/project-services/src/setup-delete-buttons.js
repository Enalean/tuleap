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

import { openModalAndReplacePlaceholders } from "@tuleap/tlp-modal";

const DELETE_BUTTONS_SELECTOR = ".project-admin-services-delete-button";
const DELETE_MODAL_ID = "project-admin-services-delete-modal";
const DELETE_MODAL_HIDDEN_INPUT_ID = "project-admin-services-delete-modal-service-id";
const DELETE_MODAL_DESCRIPTION_ID = "project-admin-services-delete-modal-description";

export function setupDeleteButtons(gettext_provider) {
    openModalAndReplacePlaceholders({
        document,
        buttons_selector: DELETE_BUTTONS_SELECTOR,
        modal_element_id: DELETE_MODAL_ID,
        hidden_input_replacement: {
            input_id: DELETE_MODAL_HIDDEN_INPUT_ID,
            hiddenInputReplaceCallback(clicked_button) {
                return clicked_button.dataset.serviceId;
            },
        },
        paragraph_replacement: {
            paragraph_id: DELETE_MODAL_DESCRIPTION_ID,
            paragraphReplaceCallback(clicked_button) {
                return gettext_provider.$gettext(
                    `You are about to delete the %{ service_name } service. Please, confirm your action`,
                    { service_name: `${clicked_button.dataset.serviceLabel}` },
                );
            },
        },
    });
}
