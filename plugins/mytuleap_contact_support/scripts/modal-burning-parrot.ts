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

import type { Modal } from "@tuleap/tlp-modal";
import { get, post } from "@tuleap/tlp-fetch";
import { createModal } from "@tuleap/tlp-modal";
import { setupContactSupportModalBurningParrot } from "./setup-contact-support-modal";
import { contactSupportModalShown } from "./modal";

let contact_support_modal: Modal | null = null;
function buildThenShowModal(mount_point: Document, modal_element: Element): void {
    if (contact_support_modal !== null) {
        showModal(contact_support_modal);
        return;
    }

    contact_support_modal = createModal(modal_element);
    contact_support_modal.addEventListener(
        "tlp-modal-shown",
        contactSupportModalShown(mount_point, post),
    );

    showModal(contact_support_modal);
}

function showModal(contact_support_modal: Modal): void {
    contact_support_modal.show();
}

setupContactSupportModalBurningParrot(document, get, buildThenShowModal);
