/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import type { get } from "@tuleap/tlp-fetch";
import { sanitize } from "dompurify";

type ShowModalFn = (mount_point: Document, modal_element: Element) => void;

export function setupContactSupportModalBurningParrot(
    mount_point: Document,
    tlp_get: typeof get,
    showModal: ShowModalFn,
): void {
    setupContactSupportModal(mount_point, "1", tlp_get, showModal);
}

export function setupContactSupportModalFlamingParrot(
    mount_point: Document,
    tlp_get: typeof get,
    showModal: ShowModalFn,
): void {
    setupContactSupportModal(mount_point, "0", tlp_get, showModal);
}

function setupContactSupportModal(
    mount_point: Document,
    is_burning_parrot_compatible: "0" | "1",
    tlp_get: typeof get,
    showModal: ShowModalFn,
): void {
    mount_point.addEventListener("DOMContentLoaded", () => {
        const help_modal_trigger = mount_point.querySelector('.help-dropdown-link[href="/help/"]');
        if (!(help_modal_trigger instanceof Element)) {
            return;
        }

        let modal_element: Element | null = null;

        help_modal_trigger.addEventListener("click", async (event: Event) => {
            event.preventDefault();

            if (modal_element === null) {
                modal_element = await insertModalElement(
                    mount_point,
                    is_burning_parrot_compatible,
                    tlp_get,
                );
            }

            showModal(mount_point, modal_element);
        });
    });
}

async function insertModalElement(
    mount_point: Document,
    is_burning_parrot_compatible: "0" | "1",
    tlp_get: typeof get,
): Promise<Element> {
    const response_modal_data = await tlp_get(
        `/plugins/mytuleap_contact_support/get-modal-content?is-burning-parrot-compatible=${encodeURI(
            is_burning_parrot_compatible,
        )}`,
    );

    const modal_fragment = sanitize(await response_modal_data.text(), {
        RETURN_DOM_FRAGMENT: true,
        RETURN_DOM_IMPORT: true,
    });
    mount_point.body.appendChild(modal_fragment);

    const modal_element = mount_point.body.querySelector(".contact-support-modal");
    if (modal_element === null) {
        throw new Error("Cannot find the contact modal support element in the DOM");
    }
    return modal_element;
}
