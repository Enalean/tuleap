/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LabelForField from "./LabelForField.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { BaseFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

describe("LabelForField", () => {
    const getWrapper = (field: Partial<BaseFieldStructure>): VueWrapper =>
        shallowMount(LabelForField, {
            props: {
                field: {
                    field_id: 123,
                    name: "summary",
                    label: "Summary",
                    required: false,
                    ...field,
                },
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

    it.each([[true], [false]])(
        "should display the label with required = %s",
        (required: boolean) => {
            const wrapper = getWrapper({
                label: "Summary",
                required,
            });

            expect(wrapper.text()).toContain("Summary");
            expect(wrapper.find("[data-test=required]").exists()).toBe(required);
            expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
            expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(false);
            expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
            expect(wrapper.find("[data-test=success]").exists()).toBe(false);
        },
    );

    it("should start edition of label", async () => {
        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "Lorem";
        await input.trigger("input");

        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(true);

        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
        expect(wrapper.find("[data-test=success]").exists()).toBe(false);
    });

    it("should start saving label", async () => {
        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "Lorem";
        await input.trigger("input");
        await input.trigger("keydown.enter");

        expect(wrapper.find("[data-test=updating]").exists()).toBe(true);

        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(false);
        expect(wrapper.find("[data-test=success]").exists()).toBe(false);
    });

    it("should not start saving label when it is empty", async () => {
        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "";
        await input.trigger("input");
        await input.trigger("keydown.enter");

        expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(true);

        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=success]").exists()).toBe(false);
    });

    it("should not start saving label when it is the same than before", async () => {
        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "Summary";
        await input.trigger("input");
        await input.trigger("keydown.enter");

        expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(false);

        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=success]").exists()).toBe(false);
    });

    it("should have save label", async () => {
        const patch = vi.spyOn(fetch_result, "patchJSON").mockReturnValue(okAsync({}));

        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "Lorem";
        await input.trigger("input");
        await input.trigger("keydown.enter");

        expect(patch).toHaveBeenCalled();
        expect(wrapper.find("[data-test=success]").exists()).toBe(true);

        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(false);
        expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
    });

    it("should display error message if any and stay in edition", async () => {
        const patch = vi
            .spyOn(fetch_result, "patchJSON")
            .mockReturnValue(errAsync(Fault.fromMessage("You cannot")));

        const wrapper = getWrapper({
            label: "Summary",
        });

        const input = wrapper.find<HTMLElement>("[data-test=input]");
        input.element.innerText = "Lorem";
        await input.trigger("input");
        await input.trigger("keydown.enter");

        expect(patch).toHaveBeenCalled();
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(true);
        expect(wrapper.find("[data-test=error-message]").text()).toBe("You cannot");
        expect(wrapper.find("[data-test=press-enter-to-save]").exists()).toBe(true);

        expect(wrapper.find("[data-test=updating]").exists()).toBe(false);
        expect(wrapper.find("[data-test=success]").exists()).toBe(false);
    });
});
