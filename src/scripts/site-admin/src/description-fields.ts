/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { openAllTargetModalsOnClick, openTargetModalIdOnClick } from "@tuleap/tlp-modal";
import { autoSubmitSwitches } from "./switches/switch-autosubmitter";

const ADD_BUTTON_ID = "add-description-field-button";
const EDIT_BUTTONS_SELECTOR = "[data-edit-field-button]";
const DELETE_BUTTONS_SELECTOR = "[data-delete-field-button]";
const REQUIRED_SWITCH_SELECTOR = "[data-description-field-required-switch]";

document.addEventListener("DOMContentLoaded", () => {
    openTargetModalIdOnClick(document, ADD_BUTTON_ID);
    openAllTargetModalsOnClick(document, EDIT_BUTTONS_SELECTOR);
    openAllTargetModalsOnClick(document, DELETE_BUTTONS_SELECTOR);
    autoSubmitSwitches(document, REQUIRED_SWITCH_SELECTOR);
});
