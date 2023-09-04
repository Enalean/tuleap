/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";
import type { Item, RootState } from "../../type";
import type { PreferenciesState } from "../../store/preferencies/preferencies-default-state";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("EmbeddedFileEditionSwitcher", () => {
    let display_in_large_mode: jest.Mock;
    let display_in_narrow_mode: jest.Mock;

    beforeEach(() => {
        display_in_large_mode = jest.fn();
        display_in_narrow_mode = jest.fn();
    });

    function getWrapper(
        preferencies: PreferenciesState,
        currently_previewed_item: Item | null,
    ): VueWrapper<InstanceType<typeof EmbeddedFileEditionSwitcher>> {
        return shallowMount(EmbeddedFileEditionSwitcher, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        preferencies: {
                            state: preferencies as PreferenciesState,
                            namespaced: true,
                            actions: {
                                displayEmbeddedInNarrowMode: display_in_narrow_mode,
                                displayEmbeddedInLargeMode: display_in_large_mode,
                            },
                        },
                    },
                    state: {
                        currently_previewed_item,
                    } as RootState,
                }),
            },
        });
    }

    it(`Given user is not in large view
        Then switch button should be check on narrow`, () => {
        const wrapper = getWrapper(
            {
                is_embedded_in_large_view: false,
            },
            null,
        );

        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-narrow]").element.checked,
        ).toBe(true);
        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-large]").element.checked,
        ).toBe(false);
    });

    it(`Embedded document is well rendered in narrow mode`, () => {
        const wrapper = getWrapper(
            {
                is_embedded_in_large_view: true,
            },
            null,
        );

        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-narrow]").element.checked,
        ).toBe(false);
        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-large]").element.checked,
        ).toBe(true);
    });

    it(`Should switch view to narrow when user click on narrow view`, () => {
        const item: Item = { id: 42, title: "my embedded document" } as Item;
        const wrapper = getWrapper(
            {
                is_embedded_in_large_view: false,
            },
            item,
        );

        wrapper.get("[data-test=view-switcher-narrow]").trigger("click");
        expect(display_in_narrow_mode).toHaveBeenCalled();
    });

    it(`Should switch view to large when user click on large view`, () => {
        const item: Item = { id: 42, title: "my embedded document" } as Item;
        const wrapper = getWrapper(
            {
                is_embedded_in_large_view: true,
            },
            item,
        );

        wrapper.get("[data-test=view-switcher-large]").trigger("click");
        expect(display_in_large_mode).toHaveBeenCalled();
    });
});
