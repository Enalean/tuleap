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
import { checkAllFieldAreFilledAndSetErrorMessage } from "../helper/form-fields-checker";
import type { GetText } from "@tuleap/vue2-gettext-init";
import { resetRestErrorAlert, setRestErrorMessage } from "../helper/rest-error-helper";

import { saveConfiguration } from "../api/manage-configuration";
import type { ErrorRest } from "../type";
import {
    resetButtonToSaveConfiguration,
    setButtonToDisabledWithSpinner,
} from "../helper/button-helper";
import { buildProgramConfiguration } from "../helper/program-configuration-builder";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const DISPLAY_ERROR_REST_ID = "program-management-save-configuration-error-rest";
const FORM_ID = "form-program-configuration";
const SUBMIT_BUTTON_ID = "program-management-admin-button-save-configuration";

export function submitConfigurationHandler(
    doc: Document,
    gettext_provider: GetText,
    program_id: number,
): void {
    const form = doc.getElementById(FORM_ID);
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const button_configuration = doc.getElementById(SUBMIT_BUTTON_ID);
    if (!(button_configuration instanceof HTMLButtonElement)) {
        throw new Error(`Button #${SUBMIT_BUTTON_ID} does not seem to exist`);
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        resetRestErrorAlert(doc, DISPLAY_ERROR_REST_ID);
        setButtonToDisabledWithSpinner(button_configuration);

        const are_filled = checkAllFieldAreFilledAndSetErrorMessage(doc, gettext_provider);
        if (!are_filled) {
            resetButtonToSaveConfiguration(button_configuration);
            return;
        }

        try {
            await saveConfiguration(buildProgramConfiguration(doc, program_id));
            window.location.reload();
        } catch (e) {
            if (!(e instanceof FetchWrapperError)) {
                throw e;
            }
            e.response
                .json()
                .then(({ error }: ErrorRest) => {
                    let error_message = error.message;
                    if (error.i18n_error_message) {
                        error_message = error.i18n_error_message;
                    }
                    setRestErrorMessage(
                        doc,
                        DISPLAY_ERROR_REST_ID,
                        error.code + " " + error_message,
                    );
                })
                .catch(() => setRestErrorMessage(doc, DISPLAY_ERROR_REST_ID, "404 Error"));
        } finally {
            resetButtonToSaveConfiguration(button_configuration);
        }
    });
}
