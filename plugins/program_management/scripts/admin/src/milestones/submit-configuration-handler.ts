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
import type { GetText } from "@tuleap/core/scripts/tuleap/gettext/gettext-init";
import { resetRestErrorAlert, setRestErrorMessage } from "../helper/rest-error-helper";

import { saveConfiguration } from "../api/manage-configuration";
import type { ErrorRest } from "../type";
import {
    resetButtonToSaveConfiguration,
    setButtonToDisabledWithSpinner,
} from "../helper/button-helper";
import { buildProgramConfiguration } from "../helper/program-configuration-builder";

export function submitConfigurationHandler(
    doc: Document,
    gettext_provider: GetText,
    program_id: number,
    use_iteration: boolean
): void {
    const button_configuration = document.getElementById(
        "program-management-admin-button-save-configuration"
    );
    if (!button_configuration || !(button_configuration instanceof HTMLButtonElement)) {
        return;
    }

    button_configuration.addEventListener("click", async () => {
        resetRestErrorAlert(doc, "program-management-save-configuration-error-rest");
        setButtonToDisabledWithSpinner(button_configuration);

        const are_filled = checkAllFieldAreFilledAndSetErrorMessage(doc, gettext_provider);
        if (!are_filled) {
            resetButtonToSaveConfiguration(button_configuration);
            return;
        }

        try {
            await saveConfiguration(buildProgramConfiguration(doc, program_id, use_iteration));
            window.location.reload();
        } catch (e) {
            e.response
                .json()
                .then(({ error }: ErrorRest) => {
                    let error_message = error.message;
                    if (error.i18n_error_message) {
                        error_message = error.i18n_error_message;
                    }
                    setRestErrorMessage(
                        doc,
                        "program-management-save-configuration-error-rest",
                        error.code + " " + error_message
                    );
                })
                .catch(() =>
                    setRestErrorMessage(
                        doc,
                        "program-management-save-configuration-error-rest",
                        "404 Error"
                    )
                );
        } finally {
            resetButtonToSaveConfiguration(button_configuration);
        }
    });
}
