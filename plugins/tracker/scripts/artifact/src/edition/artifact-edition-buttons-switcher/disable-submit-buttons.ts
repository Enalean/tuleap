/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

function setSubmitOption(artifact_form: HTMLFormElement, doc: Document, option: string): void {
    const submit_and_stay = doc.createElement("input");

    submit_and_stay.setAttribute("type", "hidden");
    submit_and_stay.setAttribute("name", option);
    submit_and_stay.setAttribute("value", "1");

    artifact_form.appendChild(submit_and_stay);
}

export const disableSubmitAfterArtifactEdition = (doc: Document): void => {
    const buttons = doc.querySelectorAll<HTMLButtonElement>(".submit-artifact-button");
    const form = doc.querySelector("form.artifact-form");
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            button.disabled = true;
            if (button.name === "submit_and_stay") {
                setSubmitOption(form, doc, "submit_and_stay");
            }
            if (button.name === "submit_and_continue") {
                setSubmitOption(form, doc, "submit_and_continue");
            }

            form.submit();
        });
    });
};
