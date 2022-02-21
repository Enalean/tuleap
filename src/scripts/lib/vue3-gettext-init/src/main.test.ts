/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { POFile } from "./main";
import { initVueGettext } from "./main";

import * as vue3_gettext from "vue3-gettext";

describe("vue3-gettext-init", () => {
    beforeEach(() => {
        document.body.dataset.userLocale = "";
    });

    describe("when a locale is defined on the document's body", () => {
        const callback = (): Promise<POFile> =>
            Promise.resolve({
                messages: {
                    "Hello world": "Bonjour monde",
                },
            });
        beforeEach(() => {
            document.body.dataset.userLocale = "fr_FR";
        });

        it("loads the translations and gives them to vue3-gettext", async () => {
            const create_gettext_spy = jest.spyOn(vue3_gettext, "createGettext");

            const gettext = await initVueGettext(callback);

            expect(create_gettext_spy).toHaveBeenCalledWith(
                expect.objectContaining({
                    translations: {
                        fr_FR: {
                            "Hello world": "Bonjour monde",
                        },
                    },
                })
            );
            expect(Object.keys(gettext).length).toBeGreaterThan(0);
        });
    });

    describe("when a locale is NOT defined on the document's body", () => {
        const callback = jest.fn(() => Promise.resolve({ messages: {} }));

        it("does not call the callback", async () => {
            await initVueGettext(callback);
            expect(callback).not.toHaveBeenCalled();
        });

        it("gives an empty translations object to vue-gettext", async () => {
            const create_gettext_spy = jest.spyOn(vue3_gettext, "createGettext");

            const gettext = await initVueGettext(callback);
            expect(create_gettext_spy).toHaveBeenCalledWith(
                expect.objectContaining({
                    translations: {},
                })
            );
            expect(Object.keys(gettext).length).toBeGreaterThan(0);
        });
    });
});
