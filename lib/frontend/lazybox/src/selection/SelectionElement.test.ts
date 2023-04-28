/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { RenderedItemStub } from "../../tests/stubs/RenderedItemStub";
import type { RenderedItem } from "../type";
import type { HostElement, SelectionElement } from "./SelectionElement";
import {
    buildClear,
    buildFocus,
    buildGetSelection,
    buildHasSelection,
    buildReplaceSelection,
    buildSelectedBadges,
    buildSelectItem,
    getContent,
    getSpan,
    onKeyUp,
    searchInputSetter,
    selectedItemSetter,
} from "./SelectionElement";
import type { SearchInput } from "../SearchInput";
import type { SelectionBadge } from "./SelectionBadge";

const noopOnSelection = (item: RenderedItem | null): void => {
    if (item !== null) {
        //Do nothing
    }
};

const PLACEHOLDER_TEXT = "I'm holding the place 8-)";

describe("SelectionElement", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("Rendering", () => {
        let multiple: boolean;
        beforeEach(() => {
            multiple = true;
        });

        const getRenderedTemplate = (host: HostElement): ShadowRoot => {
            const render = getContent(host);
            const target = doc.createElement("div") as unknown as ShadowRoot;
            render(host, target);
            return target;
        };

        const getHost = (...selected_items: RenderedItem[]): HostElement =>
            Object.assign(doc.createElement("span"), {
                multiple,
                placeholder_text: PLACEHOLDER_TEXT,
                hasSelection: () => selected_items.length > 0,
                selection_badge_callback: (item: RenderedItem) => {
                    if (item) {
                        //Do nothing
                    }
                    return doc.createElement("span") as SelectionBadge & HTMLElement;
                },
                onSelection: noopOnSelection,
                selected_items,
            }) as HostElement;

        describe("getContent()", () => {
            describe(`when multiple selection is disabled`, () => {
                beforeEach(() => {
                    multiple = false;
                });

                it(`When no item is selected, it shows a placeholder element`, () => {
                    const placeholder = selectOrThrow(
                        getRenderedTemplate(getHost()),
                        "[data-test=selection-placeholder]"
                    );
                    expect(placeholder.textContent?.trim()).toBe(PLACEHOLDER_TEXT);
                });

                it(`When an item is selected, it shows it`, () => {
                    const host = getHost(RenderedItemStub.withDefaults());
                    const content = selectOrThrow(
                        getRenderedTemplate(host),
                        "[data-test=selected-element]"
                    );
                    expect(content).toBeDefined();
                });

                it(`span_element getter finds it`, () => {
                    const host = getHost();
                    Object.assign(host, { content: () => getRenderedTemplate(host) });
                    expect(getSpan(host)).toBeDefined();
                });
            });

            describe(`when multiple selection is enabled`, () => {
                beforeEach(() => {
                    multiple = true;
                });

                it(`when no item is selected, it does not add a "Clear all" button`, () => {
                    const host = getHost();
                    const content = getRenderedTemplate(host);

                    expect(
                        content.querySelector("[data-test=clear-current-selection-button]")
                    ).toBeNull();
                });

                it(`when items are selected, it adds a "Clear all" button`, () => {
                    const host = getHost(RenderedItemStub.withDefaults());
                    const button = selectOrThrow(
                        getRenderedTemplate(host),
                        "[data-test=clear-current-selection-button]"
                    );
                    expect(button).toBeDefined();
                });

                it(`span_element getter finds it`, () => {
                    const host = getHost();
                    Object.assign(host, { content: () => getRenderedTemplate(host) });
                    expect(getSpan(host)).toBeDefined();
                });
            });
        });

        describe(`buildSelectedBadges()`, () => {
            beforeEach(() => {
                multiple = true;
            });

            it(`when the badge dispatches "remove-badge" event,
                it will be removed from selection
                and will dispatch an "open-dropdown" event`, () => {
                const first_item = RenderedItemStub.withDefaults({ id: "value-0" });
                const second_item = RenderedItemStub.withDefaults({ id: "value-1" });
                const host = getHost(first_item, second_item);
                const onSelection = vi.spyOn(host, "onSelection");
                const dispatch = vi.spyOn(host, "dispatchEvent");

                const badges = buildSelectedBadges(host);
                expect(badges).toHaveLength(2);
                badges[1].dispatchEvent(new CustomEvent("remove-badge"));

                expect(onSelection).toHaveBeenCalledWith([first_item.value]);
                expect(dispatch.mock.calls[0][0].type).toBe("open-dropdown");
            });
        });
    });

    describe(`events`, () => {
        it(`when I press the "enter" key while focusing the selection,
            it will dispatch an "open-dropdown" event
            and will stop propagation to avoid triggering EventManager code`, () => {
            const host = doc.createElement("span") as HostElement;
            const dispatch = vi.spyOn(host, "dispatchEvent");
            const event = new KeyboardEvent("keyup", { key: "Enter" });
            const stopPropagation = vi.spyOn(event, "stopPropagation");

            onKeyUp(host, event);

            expect(dispatch.mock.calls[0][0].type).toBe("open-dropdown");
            expect(stopPropagation).toHaveBeenCalled();
        });
    });

    describe(`setFocus()`, () => {
        it(`sets focus to its inner span element`, () => {
            const span_element = doc.createElement("span");
            const focus = vi.spyOn(span_element, "focus");
            const host = { span_element } as HostElement;

            buildFocus(host)();

            expect(focus).toHaveBeenCalled();
        });
    });

    describe(`selection methods`, () => {
        let multiple: boolean;
        beforeEach(() => {
            multiple = true;
        });

        const getHost = (...selected_items: RenderedItem[]): HostElement => {
            return {
                multiple,
                selected_items,
                onSelection: noopOnSelection,
            } as HostElement;
        };

        describe(`getSelection()`, () => {
            it(`returns the list of selected items`, () => {
                const host = getHost(
                    RenderedItemStub.withDefaults({ id: "value-1" }),
                    RenderedItemStub.withDefaults({ id: "value-2" })
                );
                expect(buildGetSelection(host)()).toBe(host.selected_items);
            });
        });

        describe(`replaceSelection()`, () => {
            it(`replaces the previous selection by the new one`, () => {
                const host = getHost(
                    RenderedItemStub.withDefaults({ id: "value-1" }),
                    RenderedItemStub.withDefaults({ id: "value-2" })
                );
                const onSelection = vi.spyOn(host, "onSelection");

                const new_item_1 = RenderedItemStub.withDefaults({ id: "value-3" });
                const new_item_2 = RenderedItemStub.withDefaults({ id: "value-4" });

                buildReplaceSelection(host)([new_item_1, new_item_2]);

                expect(host.selected_items).toHaveLength(2);
                expect(host.selected_items).toContain(new_item_1);
                expect(host.selected_items).toContain(new_item_2);
                expect(onSelection).toHaveBeenCalledWith([new_item_1.value, new_item_2.value]);
            });
        });

        describe("hasSelection()", () => {
            it("should return false when no item is selected", () => {
                const host = getHost();
                expect(buildHasSelection(host)()).toBe(false);
            });

            it("should return true when an item is selected", () => {
                const host = getHost(RenderedItemStub.withDefaults());
                expect(buildHasSelection(host)()).toBe(true);
            });
        });

        describe("selectItem()", () => {
            it.each([
                ["when it is already selected", { is_selected: true }],
                ["when it is disabled", { is_disabled: true }],
            ])("should not select the item %s", (when, item_partial) => {
                const selected_item = RenderedItemStub.withDefaults(item_partial);
                const host = getHost(selected_item);
                const onSelection = vi.spyOn(host, "onSelection");

                buildSelectItem(host)(selected_item);

                expect(onSelection).not.toHaveBeenCalled();
            });

            it(`when multiple selection is disabled,
                it should select the item and call the onSelection callback with its value`, () => {
                multiple = false;
                const host = getHost();
                const onSelection = vi.spyOn(host, "onSelection");
                const new_selection = RenderedItemStub.withDefaults();

                buildSelectItem(host)(new_selection);

                expect(onSelection).toHaveBeenCalledWith(new_selection.value);
            });

            it(`when multiple selection is allowed,
                it should add the item to the selection
                and call the onSelection callback with all selected values`, () => {
                const previous_value = { id: 451 };
                const new_value = { id: 958 };
                const host = getHost(RenderedItemStub.withDefaults({ value: previous_value }));
                const onSelection = vi.spyOn(host, "onSelection");
                const new_selection = RenderedItemStub.withDefaults({ value: new_value });

                buildSelectItem(host)(new_selection);

                expect(onSelection).toHaveBeenCalledWith([previous_value, new_value]);
            });
        });

        describe("clearSelection()", () => {
            it(`when multiple selection is disabled,
                it should clear the selection and call the onSelection callback with a null value`, () => {
                multiple = false;
                const host = getHost(RenderedItemStub.withDefaults());
                const onSelection = vi.spyOn(host, "onSelection");

                buildClear(host)();

                expect(host.selected_items).toHaveLength(0);
                expect(onSelection).toHaveBeenCalledWith(null);
            });

            it(`when multiple selection is allowed,
                it should clear the selection and call the onSelection callback with an empty array`, () => {
                const host = getHost(RenderedItemStub.withDefaults());
                const onSelection = vi.spyOn(host, "onSelection");

                buildClear(host)();

                expect(host.selected_items).toHaveLength(0);
                expect(onSelection).toHaveBeenCalledWith([]);
            });
        });
    });

    describe(`search input setter`, () => {
        it(`when it receives "backspace-pressed" from the search input,
            it will remove the last selected item and call the onSelection callback`, () => {
            const first_item = RenderedItemStub.withDefaults({ value: { id: 0 } });
            const second_item = RenderedItemStub.withDefaults({ value: { id: 1 } });
            const host = {
                multiple: true,
                selected_items: [first_item, second_item],
                onSelection: noopOnSelection,
            } as HostElement;
            const onSelection = vi.spyOn(host, "onSelection");
            const search_input = doc.createElement("span") as SearchInput & HTMLElement;

            searchInputSetter(host, search_input);
            search_input.dispatchEvent(new CustomEvent("backspace-pressed"));

            expect(onSelection).toHaveBeenCalledWith([first_item.value]);
            expect(host.selected_items).toHaveLength(1);
            expect(host.selected_items).not.toContain(second_item);
        });
    });

    describe("selected item setter", () => {
        let multiple: boolean;
        beforeEach(() => {
            multiple = true;
        });

        const getHost = (): SelectionElement => {
            const search_input = { placeholder: "puzzle" } as SearchInput;
            return {
                multiple,
                placeholder_text: PLACEHOLDER_TEXT,
                search_input,
            } as SelectionElement;
        };

        it(`will return an empty array by default`, () => {
            const return_values = selectedItemSetter(getHost(), [], undefined);
            expect(return_values).toStrictEqual([]);
        });

        it(`will do nothing when new value is the same as old value`, () => {
            const old_values = [RenderedItemStub.withDefaults({ is_selected: false })];

            const return_values = selectedItemSetter(getHost(), old_values, old_values);

            expect(return_values).toBe(old_values);
            expect(old_values[0].is_selected).toBe(false);
            expect(old_values[0].element.getAttribute("aria-selected")).toBe("false");
        });

        it(`will mark old values as NOT selected in the dropdown
            and mark new values as selected in the dropdown`, () => {
            const first_old_value = RenderedItemStub.withDefaults({ is_selected: true });
            first_old_value.element.setAttribute("aria-selected", "true");
            const second_old_value = RenderedItemStub.withDefaults({ is_selected: true });
            second_old_value.element.setAttribute("aria-selected", "true");
            const new_values = [
                RenderedItemStub.withDefaults({ is_selected: false }),
                RenderedItemStub.withDefaults({ is_selected: false }),
            ];

            const return_values = selectedItemSetter(getHost(), new_values, [
                first_old_value,
                second_old_value,
            ]);

            expect(return_values).toBe(new_values);
            const [first_value, second_value] = return_values;
            expect(first_value.is_selected).toBe(true);
            expect(first_value.element.getAttribute("aria-selected")).toBe("true");
            expect(second_value.is_selected).toBe(true);
            expect(second_value.element.getAttribute("aria-selected")).toBe("true");
            expect(first_old_value.is_selected).toBe(false);
            expect(first_old_value.element.getAttribute("aria-selected")).toBe("false");
            expect(second_old_value.is_selected).toBe(false);
            expect(second_old_value.element.getAttribute("aria-selected")).toBe("false");
        });

        describe(`when multiple selection is allowed`, () => {
            beforeEach(() => {
                multiple = true;
            });

            it(`and when the new selection is not empty,
                it will clear the search input placeholder`, () => {
                const new_value = [RenderedItemStub.withDefaults({ is_selected: false })];
                const host = getHost();

                selectedItemSetter(host, new_value, []);

                expect(host.search_input.placeholder).toBe("");
            });

            it(`and when the new selection is empty,
                it will assign the search input placeholder`, () => {
                const host = getHost();

                selectedItemSetter(
                    host,
                    [],
                    [RenderedItemStub.withDefaults({ is_selected: true })]
                );

                expect(host.search_input.placeholder).toBe(PLACEHOLDER_TEXT);
            });
        });
    });
});
