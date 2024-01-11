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

import { describe, it, expect, beforeEach, vi } from "vitest";
import Vue from "vue";
import GettextPlugin from "vue-gettext";
import type { POGettextPluginPOFile } from "./main";
import { initVueGettext } from "./main";
import type { VueConfiguration } from "vue/types/vue";

vi.mock("vue", () => {
    return {
        __esModule: true,
        default: {
            use: vi.fn(),
            config: {},
        },
    };
});
vi.mock("vue-gettext");

describe(`vue-gettext-init`, () => {
    beforeEach(() => {
        vi.resetModules();
        document.body.dataset.userLocale = "";
        Vue.config = {} as VueConfiguration;
    });

    describe(`initVueGettext()`, () => {
        let callback: () => PromiseLike<POGettextPluginPOFile>;

        const init = async (): Promise<void> => {
            await initVueGettext(Vue, callback);
        };

        describe(`when a locale is defined on the document's body`, () => {
            beforeEach(() => {
                document.body.dataset.userLocale = "fr_FR";
                callback = (): PromiseLike<POGettextPluginPOFile> =>
                    Promise.resolve({
                        translations: {
                            "": {
                                "Hello world": { msgid: "Hello world", msgstr: ["Bonjour monde"] },
                            },
                        },
                    });
            });

            it(`loads the PO file, maps it to vue2-gettext format
                and gives the translations to vue-gettext`, async () => {
                await init();
                expect(Vue.use).toHaveBeenCalledWith(
                    GettextPlugin,
                    expect.objectContaining({
                        translations: { fr_FR: { "Hello world": "Bonjour monde" } },
                    }),
                );
            });
            it(`sets vue-gettext's language config to the document locale`, async () => {
                await init();
                expect(Vue.config.language).toBe("fr_FR");
            });

            it(`when it fails to load the translations,
                it will give an empty translations object to vue-gettext`, async () => {
                callback = (): PromiseLike<never> => Promise.reject("404 Not found");
                await init();
                expect(Vue.use).toHaveBeenCalledWith(
                    GettextPlugin,
                    expect.objectContaining({ translations: {} }),
                );
            });
        });

        describe(`when a locale is NOT defined on the document's body`, () => {
            beforeEach(async () => {
                callback = vi
                    .fn()
                    .mockImplementation(() => Promise.reject("Should not have been called"));
                await init();
            });
            it(`does not try to load translations`, () => expect(callback).not.toHaveBeenCalled());
            it(`does not set vue-gettext's language config`, () =>
                expect(Vue.config.language).toBeUndefined());
            it(`gives an empty translations object to vue-gettext`, () =>
                expect(Vue.use).toHaveBeenCalledWith(
                    GettextPlugin,
                    expect.objectContaining({ translations: {} }),
                ));
        });
    });
});
