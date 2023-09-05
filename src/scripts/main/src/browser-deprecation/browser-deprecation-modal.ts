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

import { markAndCheckBrowserDeprecationAcknowledgement } from "./mark-deprecation-acknowledgement";

export function displayBrowserDeprecationModalIfNeeded(
    mount_point: Document,
    showModal: (modal: Element) => void,
    showNonDismissibleModal: (modal: Element) => void,
    storage: Storage,
): void {
    const browser_deprecation_modal_element = mount_point.getElementById(
        "browser-deprecation-modal",
    );
    if (browser_deprecation_modal_element === null) {
        throw new Error("Browser deprecation modal #browser-deprecation-modal not found");
    }

    const can_be_dismissed =
        !browser_deprecation_modal_element.hasAttribute("data-non-dismissible");

    if (can_be_dismissed && !markAndCheckBrowserDeprecationAcknowledgement(storage)) {
        showModal(browser_deprecation_modal_element);
    }
    if (!can_be_dismissed) {
        showNonDismissibleModal(browser_deprecation_modal_element);
    }
}
