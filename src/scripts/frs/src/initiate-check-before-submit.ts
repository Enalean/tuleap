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

import { get } from "@tuleap/tlp-fetch";

export function initiateCheckBeforeSubmit(): void {
    const form = document.getElementById("frs-release-form");
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.addEventListener("submit", (event: Event) => {
        event.preventDefault();
        event.stopPropagation();

        clearErrorMessage();
        checkFileUploadSize(form, checkParametersOnServer);
    });

    function checkFileUploadSize(
        form: HTMLFormElement,
        onsuccess_callback: (form: HTMLFormElement) => void,
    ): void {
        const input_files = form.querySelectorAll<HTMLInputElement>("input[type=file]");

        if (input_files.length === 0) {
            onsuccess_callback(form);
            return;
        }

        const total_bytes = [...input_files].reduce((total, input_file): number => {
            const files = input_file.files;

            if (files === null) {
                return total;
            }

            return [...files].reduce((total, file) => total + file.size, total);
        }, 0);

        const max_file_size = parseInt(
            form.querySelector<HTMLInputElement>("input[name=MAX_FILE_SIZE]")?.value || "0",
            10,
        );
        if (total_bytes < max_file_size) {
            onsuccess_callback(form);
        } else {
            displayErrorMessage(String(form.dataset.sizeError));
        }
    }

    function checkParametersOnServer(form: HTMLFormElement): void {
        get(
            "frsajax.php?group_id=" +
                form.dataset.projectId +
                "&action=" +
                (form.dataset.isUpdate === "1" ? "validator_frs_update" : "validator_frs_create") +
                "&package_id=" +
                encodeURIComponent(
                    String(document.querySelector<HTMLInputElement>("#package_id")?.value),
                ) +
                "&date=" +
                encodeURIComponent(
                    String(
                        document.querySelector<HTMLInputElement>("#frs-release-date-picker")?.value,
                    ),
                ) +
                "&name=" +
                encodeURIComponent(
                    String(document.querySelector<HTMLInputElement>("#release_name")?.value),
                ) +
                (form.dataset.isUpdate === "1"
                    ? "&release_id=" +
                      encodeURIComponent(
                          String(document.querySelector<HTMLInputElement>("#release_id")?.value),
                      )
                    : ""),
        )
            .then(() => form.submit())
            .catch(async (error) => {
                displayErrorMessage(await error.response.json());
            });
    }

    function clearErrorMessage(): void {
        const container = document.getElementById("frs-release-feedback");
        if (container === null) {
            return;
        }

        container.textContent = "";
    }

    function displayErrorMessage(error: string): void {
        const container = document.getElementById("frs-release-feedback");
        if (container === null) {
            return;
        }

        container.textContent = error;

        container.scrollIntoView({
            behavior: "smooth",
            block: "center",
        });
    }
}
