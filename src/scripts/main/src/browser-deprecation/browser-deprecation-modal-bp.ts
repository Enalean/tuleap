/**
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

import { displayBrowserDeprecationModalIfNeeded } from "./browser-deprecation-modal";
import { createModal, Modal } from "tlp";

class NonDismissibleModal extends Modal {
    override hide(): void {
        // Empty on purpose, we do not want this modal to be hidden
    }
}

document.addEventListener("DOMContentLoaded", () => {
    displayBrowserDeprecationModalIfNeeded(
        document,
        (modal_element: Element) => {
            createModal(modal_element).show();
        },
        (modal_element: Element) => {
            new NonDismissibleModal(document, modal_element).show();
        },
        window.localStorage,
    );
});
