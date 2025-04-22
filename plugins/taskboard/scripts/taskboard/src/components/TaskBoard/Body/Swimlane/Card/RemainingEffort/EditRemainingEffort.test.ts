/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../../../helpers/global-options-for-test";
import EditRemainingEffort from "./EditRemainingEffort.vue";
import type { Card } from "../../../../../../type";
import emitter from "../../../../../../helpers/emitter";

describe("EditRemainingEffort", () => {
    const mock_remove_remaining_effort_from_edit_mode = jest.fn();
    const mock_save_remaining_effort = jest.fn();

    function getWrapper(
        is_being_saved = false,
    ): VueWrapper<InstanceType<typeof EditRemainingEffort>> {
        return shallowMount(EditRemainingEffort, {
            props: {
                card: {
                    id: 42,
                    color: "fiesta-red",
                    remaining_effort: {
                        value: 3.14,
                        is_in_edit_mode: true,
                        is_being_saved,
                    },
                } as Card,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            mutations: {
                                removeRemainingEffortFromEditMode:
                                    mock_remove_remaining_effort_from_edit_mode,
                            },
                            actions: {
                                saveRemainingEffort: mock_save_remaining_effort,
                                loadSwimlanes: jest.fn(),
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    afterEach(() => {
        jest.clearAllMocks();
    });

    it("Displays a text input", () => {
        const wrapper = getWrapper();

        expect(wrapper.attributes("type")).toBe("text");
        expect(wrapper.attributes("aria-label")).toBe("New remaining effort");
    });

    it("Does not save anything if user hit enter but didn't change the initial value", async () => {
        const wrapper = getWrapper();

        await wrapper.trigger("keyup.enter");
        expect(mock_remove_remaining_effort_from_edit_mode).not.toHaveBeenCalled();
    });

    it(`Does not save anything if the remaining effort is already being saved`, async () => {
        const wrapper = getWrapper(true);

        await wrapper.trigger("keyup.enter");
        expect(mock_save_remaining_effort).not.toHaveBeenCalled();
    });

    it("Saves the new value if the user hits enter", async () => {
        const wrapper = getWrapper();
        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);
        await wrapper.trigger("keyup.enter");

        const card = wrapper.props("card");
        expect(mock_save_remaining_effort).toHaveBeenCalledWith(expect.anything(), {
            card,
            value,
        });
        expect(card.remaining_effort?.is_in_edit_mode).toBe(true);
    });

    it(`Saves the new value if the user clicks on save button (that is outside of this component)`, async () => {
        const wrapper = getWrapper();

        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);

        const card = wrapper.props("card");
        emitter.emit("save-card-edition", card);

        expect(mock_save_remaining_effort).toHaveBeenCalledWith(expect.anything(), {
            card,
            value,
        });
    });

    it(`Cancels the edition of the remaining effort if the user clicks on cancel button (that is outside of this component)`, async () => {
        const wrapper = getWrapper();

        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);

        const card = wrapper.props("card");
        emitter.emit("cancel-card-edition", card);

        expect(mock_remove_remaining_effort_from_edit_mode).toHaveBeenCalledWith(
            expect.anything(),
            card,
        );
        expect(card.remaining_effort?.value).toBe(3.14);
    });

    it(`does not save anynthing if user clicks on save button for another card`, async () => {
        const wrapper = getWrapper();

        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);

        const card = wrapper.props("card");
        emitter.emit("save-card-edition", {} as Card);

        expect(mock_remove_remaining_effort_from_edit_mode).not.toHaveBeenCalled();
        expect(card.remaining_effort?.is_in_edit_mode).toBe(true);
    });

    it(`does not cancel the edition of the remaining effort if user clicks on cancel button for another card`, async () => {
        const wrapper = getWrapper();

        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);

        const card = wrapper.props("card");
        emitter.emit("cancel-card-edition", {} as Card);

        expect(card.remaining_effort?.is_in_edit_mode).toBe(true);
    });

    it("Adjust the size of the input whenever user enters digits", async () => {
        const wrapper = getWrapper();

        let value = "3";
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);
        expect(wrapper.classes()).toStrictEqual(["taskboard-card-remaining-effort-input"]);

        value = "3.14";
        await input.setValue(value);
        expect(wrapper.classes()).toContain("taskboard-card-remaining-effort-input-width-40");

        value = "3.14159265358979323846264338327950288";
        await input.setValue(value);
        expect(wrapper.classes()).toContain("taskboard-card-remaining-effort-input-width-60");
    });

    it("emits the `editor-closed` event after saving", async () => {
        const wrapper = getWrapper();

        const value = 42;
        const input = wrapper.get<HTMLInputElement>("[data-test=remaining-effort]");
        await input.setValue(value);
        await wrapper.trigger("keyup.enter");

        expect(wrapper.emitted()).toHaveProperty("editor-closed");
    });
});
