/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { getHTMLSelectElementFromId } from "./HTML_element_extractor";
import { resetErrorOnSelectField, setErrorMessageOnSelectField } from "./form-field-error-helper";
import type { GetText } from "@tuleap/gettext";
import {
    PERMISSION_PRIORITIZE_ID,
    PLANNABLE_TRACKERS_ID,
} from "../milestones/init-list-pickers-milestone-section";

export function checkAllFieldAreFilledAndSetErrorMessage(
    doc: Document,
    gettext_provider: GetText,
): boolean {
    const program_increment_tracker_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-program-increment-tracker",
    );

    const plannable_trackers_element = getHTMLSelectElementFromId(doc, PLANNABLE_TRACKERS_ID);
    const permission_prioritize_element = getHTMLSelectElementFromId(doc, PERMISSION_PRIORITIZE_ID);

    resetErrorOnSelectField(program_increment_tracker_element);
    resetErrorOnSelectField(plannable_trackers_element);
    resetErrorOnSelectField(permission_prioritize_element);

    let are_filled = true;

    if (program_increment_tracker_element.selectedOptions.length === 0) {
        setErrorMessageOnSelectField(
            program_increment_tracker_element,
            gettext_provider.gettext(
                "This field is mandatory, please choose a tracker as Program Increment in the list.",
            ),
        );
        are_filled = false;
    }

    if (plannable_trackers_element.selectedOptions.length === 0) {
        setErrorMessageOnSelectField(
            plannable_trackers_element,
            gettext_provider.gettext(
                "This field is mandatory, please choose one or several trackers in the list.",
            ),
        );
        are_filled = false;
    }

    if (permission_prioritize_element.selectedOptions.length === 0) {
        setErrorMessageOnSelectField(
            permission_prioritize_element,
            gettext_provider.gettext(
                "This field is mandatory, please choose one or several groups in the list.",
            ),
        );
        are_filled = false;
    }

    return are_filled;
}
