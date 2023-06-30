/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

import { post } from "@tuleap/tlp-fetch";

let abort_controller = new AbortController();
let password_validators: number[] = [];

async function checkPassword(password: string): Promise<void> {
    abort_controller.abort();
    abort_controller = new AbortController();

    const form_data = new FormData();
    form_data.set("form_pw", password);

    try {
        const response = await post("/include/check_pw.php", {
            body: form_data,
            signal: abort_controller.signal,
        });

        const data = await response.json();

        if (toggleErrorMessages(data)) {
            setRobustnessToBad();
        } else {
            setRobustnessToGood();
        }
    } catch (e) {
        if (!(e instanceof Error) || e.name !== "AbortError") {
            throw e;
        }
    }
}

function toggleErrorMessages(data: number[]): boolean {
    let has_errors = false;
    password_validators.forEach((key: number): void => {
        [...document.getElementsByClassName("password_validator_msg_" + key)].forEach(
            (element: Element): void => {
                if (data.indexOf(key) >= 0) {
                    has_errors = true;

                    element.classList.add("tlp-text-danger");
                    element.classList.remove("tlp-text-success");

                    [...element.getElementsByTagName("i")].forEach(
                        (icon_element: Element): void => {
                            icon_element.classList.add("fa-xmark", "password-strategy-bad");
                            icon_element.classList.remove("fa-check", "password-strategy-good");
                        }
                    );
                } else {
                    element.classList.add("tlp-text-success");
                    element.classList.remove("tlp-text-danger");

                    [...element.getElementsByTagName("i")].forEach(
                        (icon_element: Element): void => {
                            icon_element.classList.add("fa-check", "password-strategy-good");
                            icon_element.classList.remove("fa-xmark", "password-strategy-bad");
                        }
                    );
                }
            }
        );
    });

    return has_errors;
}

function setRobustnessToGood(): void {
    [...document.getElementsByClassName("robustness")].forEach((element: Element): void => {
        element.classList.remove("bad");
        element.classList.add("good");
    });

    removeInProgressRobustnessComputation();
}

function setRobustnessToBad(): void {
    [...document.getElementsByClassName("robustness")].forEach((element: Element): void => {
        element.classList.remove("good");
        element.classList.add("bad");
    });

    removeInProgressRobustnessComputation();
}

function removeInProgressRobustnessComputation(): void {
    document.querySelectorAll(".robustness .fa-circle-notch").forEach((element: Element): void => {
        element.classList.remove("fa-circle-notch", "fa-spin");
    });
}

// We are wrapping a function that could use anything
/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * Simplified version of debounce function of Underscore.js
 *
 * @see http://underscorejs.org/#debounce
 */
function debounce<F extends (...args: any[]) => any>(
    func: F,
    wait: number
): (...args: Parameters<F>) => ReturnType<F> {
    let timeout: ReturnType<typeof setTimeout>;
    return (...args: Parameters<F>): ReturnType<F> => {
        let result: any;

        timeout && clearTimeout(timeout);
        timeout = setTimeout(function () {
            result = func(...args);
        }, wait);

        return result;
    };
}
/* eslint-enable */

document.addEventListener("DOMContentLoaded", () => {
    setRobustnessToBad();

    const form_password_element = document.getElementById("form_pw");
    if (!(form_password_element instanceof HTMLInputElement)) {
        throw new Error("#form_pw should be an input element");
    }

    password_validators = JSON.parse(form_password_element.dataset.passwordValidators ?? "[]");

    const debouncedCheckPassword = debounce(checkPassword, 300);

    const check_password_action = async (): Promise<void> => {
        document
            .querySelectorAll(".robustness .fa-xmark, .robustness .fa-check")
            .forEach((element: Element): void => {
                element.classList.remove("fa-xmark", "fa-check");
                element.classList.add("fa-circle-notch", "fa-spin");
            });

        await debouncedCheckPassword(form_password_element.value);
    };

    form_password_element.addEventListener("keyup", check_password_action);
    form_password_element.addEventListener("paste", check_password_action);
    form_password_element.addEventListener("change", check_password_action);

    form_password_element.addEventListener("focus", (): void => {
        [...document.getElementsByClassName("account-security-password-robustness")].forEach(
            (element: Element): void => {
                element.classList.remove("account-security-password-robustness-hidden");
            }
        );
    });
});
