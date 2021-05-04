/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { clickOnElement, focusElement } from "./trigger-datashortcut-element";
import { moveFocus } from "./move-focus";

import { NEXT } from "../type";
import type { StatusButton } from "../type";

export function markTestAndJumpToNext(doc: Document, status_button: StatusButton): void {
    focusNextTest(doc);

    clickOnElement(doc, status_button);

    const focused_test_tab_selector = "[data-navigation-test-link]:focus";
    clickOnElement(doc, focused_test_tab_selector);
}

function focusNextTest(doc: Document): void {
    const active_test_tab_selector = "[data-navigation-test-link][tabindex='0']";
    focusElement(doc, active_test_tab_selector);

    moveFocus(doc, NEXT);
}
