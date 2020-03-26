/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import Vue from "vue";
import GettextPlugin from "vue-gettext";
import { initVueGettext, POFile } from "./vue-gettext-init";

jest.mock("vue", () => {
    return {
        __esModule: true,
        default: {
            use: jest.fn(),
            config: {},
        },
    };
});
jest.mock("vue-gettext");

describe(`vue-gettext-init`, () => {
    beforeEach(() => {
        jest.resetModules();
        delete document.body.dataset.userLocale;
        delete Vue.config.language;
    });

    describe(`when a locale is defined on the document's body`, () => {
        const callback = (): Promise<POFile> =>
            Promise.resolve({
                messages: {
                    "Hello world": "Bonjour monde",
                },
            });
        beforeEach(async () => {
            document.body.dataset.userLocale = "fr_FR";
            await initVueGettext(Vue, callback);
        });

        it(`loads the translations and gives them to vue-gettext`, () =>
            expect(Vue.use).toHaveBeenCalledWith(
                GettextPlugin,
                expect.objectContaining({
                    translations: {
                        fr_FR: {
                            "Hello world": "Bonjour monde",
                        },
                    },
                })
            ));

        it(`sets vue-gettext's language config to the document locale`, () =>
            expect(Vue.config.language).toEqual("fr_FR"));
    });

    describe(`when a locale is NOT defined on the document's body`, () => {
        const callback = jest.fn().mockImplementation(() => Promise.resolve({ messages: {} }));
        beforeEach(async () => {
            await initVueGettext(Vue, callback);
        });
        it(`does not call the callback`, () => expect(callback).not.toHaveBeenCalled());
        it(`does not set vue-gettext's language config`, () =>
            expect(Vue.config.language).not.toBeDefined());
        it(`gives an empty translations object to vue-gettext`, () =>
            expect(Vue.use).toHaveBeenCalledWith(
                GettextPlugin,
                expect.objectContaining({
                    translations: {},
                })
            ));
    });
});
