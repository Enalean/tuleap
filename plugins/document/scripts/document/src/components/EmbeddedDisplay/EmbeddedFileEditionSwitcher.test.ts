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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";
import type { EmbeddedFileDisplayPreference, Item, RootState } from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE, EMBEDDED_FILE_DISPLAY_NARROW } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { PROJECT, USER_ID } from "../../configuration-keys";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";
import * as display_preferences from "../../helpers/preferences/embedded-file-display-preferences";
import { Option } from "@tuleap/option";

describe("EmbeddedFileEditionSwitcher", () => {
    function getWrapper(
        embedded_file_display_preference: EmbeddedFileDisplayPreference,
        currently_previewed_item: Item | null,
    ): VueWrapper<InstanceType<typeof EmbeddedFileEditionSwitcher>> {
        return shallowMount(EmbeddedFileEditionSwitcher, {
            props: { embedded_file_display_preference },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        currently_previewed_item,
                    } as RootState,
                }),
                provide: {
                    [USER_ID.valueOf()]: 254,
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    it(`Given user is not in large view
        Then switch button should be check on narrow`, () => {
        const wrapper = getWrapper(EMBEDDED_FILE_DISPLAY_NARROW, null);

        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-narrow]").element.checked,
        ).toBe(true);
        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-large]").element.checked,
        ).toBe(false);
    });

    it(`Embedded document is well rendered in narrow mode`, () => {
        const wrapper = getWrapper(EMBEDDED_FILE_DISPLAY_LARGE, null);

        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-narrow]").element.checked,
        ).toBe(false);
        expect(
            wrapper.get<HTMLInputElement>("[data-test=view-switcher-large]").element.checked,
        ).toBe(true);
    });

    it(`Should switch view to narrow when user click on narrow view`, () => {
        const display_in_narrow_mode = vi
            .spyOn(display_preferences, "displayEmbeddedInNarrowMode")
            .mockResolvedValue(Option.fromValue(EMBEDDED_FILE_DISPLAY_NARROW));
        const item: Item = { id: 42, title: "my embedded document" } as Item;
        const wrapper = getWrapper(EMBEDDED_FILE_DISPLAY_NARROW, item);

        wrapper.get("[data-test=view-switcher-narrow]").trigger("click");
        expect(display_in_narrow_mode).toHaveBeenCalled();
    });

    it(`Should switch view to large when user click on large view`, () => {
        const display_in_large_mode = vi
            .spyOn(display_preferences, "displayEmbeddedInLargeMode")
            .mockResolvedValue(Option.fromValue(EMBEDDED_FILE_DISPLAY_LARGE));
        const item: Item = { id: 42, title: "my embedded document" } as Item;
        const wrapper = getWrapper(EMBEDDED_FILE_DISPLAY_LARGE, item);

        wrapper.get("[data-test=view-switcher-large]").trigger("click");
        expect(display_in_large_mode).toHaveBeenCalled();
    });
});
