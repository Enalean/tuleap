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

const PASSWORD_CHARSET = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&%#|-!{?*+";

function getRandomChar(): string {
    const random_byte = new Uint8Array(1);
    window.crypto.getRandomValues(random_byte);

    if (random_byte[0] >= PASSWORD_CHARSET.length) {
        return getRandomChar();
    }

    return PASSWORD_CHARSET[random_byte[0]];
}

function generate(entropy_bits: number): string {
    const pass_length = Math.ceil(entropy_bits / (Math.log(PASSWORD_CHARSET.length) / Math.LN2));
    let pass = "";
    for (let i = 0; i < pass_length; i++) {
        pass += getRandomChar();
    }
    return pass;
}

document.addEventListener("DOMContentLoaded", () => {
    const form_password_element = document.getElementById("form_pw");
    if (!(form_password_element instanceof HTMLInputElement)) {
        throw new Error("#form_pw should be an input element");
    }

    const form_password_generate = document.getElementById("form_pw_generate");
    if (form_password_generate === null) {
        throw new Error("#form_password_generate should be present");
    }

    form_password_generate.addEventListener("click", () => {
        form_password_element.value = generate(128);
    });
});

export {};
