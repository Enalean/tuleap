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

import { get, post } from "../../../src/themes/tlp/src/js/fetch-wrapper";
import { setupContactSupportModalFlamingParrot } from "./setup-contact-support-modal";
import { contactSupportModalShown } from "./modal";

let jquery_modal_element: JQuery<Element> | null = null;
function buildThenShowModal(mount_point: Document, modal_element: Element): void {
    if (jquery_modal_element !== null) {
        showModal(jquery_modal_element);
        return;
    }

    jquery_modal_element = jQuery(modal_element);
    jquery_modal_element.on("shown", contactSupportModalShown(mount_point, post));
    showModal(jquery_modal_element);
}

function showModal(contact_support_modal: JQuery<Element>): void {
    contact_support_modal.modal("show");
}

setupContactSupportModalFlamingParrot(document, get, buildThenShowModal);
