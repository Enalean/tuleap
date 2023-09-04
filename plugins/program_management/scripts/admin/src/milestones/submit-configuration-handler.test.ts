/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { submitConfigurationHandler } from "./submit-configuration-handler";
import type { GetText } from "@tuleap/vue2-gettext-init";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`submitConfigurationHandler`, () => {
    const gettext: GetText = {
        gettext: (msgid: string) => {
            return msgid;
        },
    } as GetText;

    it(`returns when form is not displayed (aka no team have been configured)`, () => {
        const program_id = 101;
        const createDocument = (): Document => document.implementation.createHTMLDocument();

        expect(() => submitConfigurationHandler(createDocument(), gettext, program_id)).not.toThrow(
            Error,
        );
    });

    it(`throws when  button is not found`, () => {
        const form = document.createElement("form");
        form.id = "form-program-configuration";

        const doc = createDocument();
        doc.body.appendChild(form);

        const program_id = 101;

        expect(() => submitConfigurationHandler(doc, gettext, program_id)).toThrow(Error);
    });
});
