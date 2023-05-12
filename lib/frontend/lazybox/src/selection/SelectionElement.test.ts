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
import { LazyboxItemStub } from "../../tests/stubs/LazyboxItemStub";
import type { LazyboxItem } from "../GroupCollection";
import type { HostElement, SelectionElement } from "./SelectionElement";
import {
    buildClear,
    buildIsSelected,
    buildReplaceSelection,
    buildSelectedBadges,
    buildSelectItem,
    getContent,
    observeSelectedItems,
    searchInputSetter,
} from "./SelectionElement";
import type { SearchInput } from "../SearchInput";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import { SelectionBadgeCallbackStub } from "../../tests/stubs/SelectionBadgeCallbackStub";

const noopOnSelection = (item: LazyboxItem | null): void => {
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

        const getHost = (...selected_items: LazyboxItem[]): HostElement =>
            Object.assign(doc.createElement("span"), {
                multiple,
                placeholder_text: PLACEHOLDER_TEXT,
                selection_badge_callback: SelectionBadgeCallbackStub.build(),
                templating_callback: TemplatingCallbackStub.build(),
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
                    const host = getHost(LazyboxItemStub.withDefaults());
                    const content = selectOrThrow(
                        getRenderedTemplate(host),
                        "[data-test=selected-element]"
                    );
                    expect(content).toBeDefined();
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
                    const host = getHost(LazyboxItemStub.withDefaults());
                    const button = selectOrThrow(
                        getRenderedTemplate(host),
                        "[data-test=clear-current-selection-button]"
                    );
                    expect(button).toBeDefined();
                });
            });
        });

        describe(`buildSelectedBadges()`, () => {
            let first_item: LazyboxItem, second_item: LazyboxItem, target: ShadowRoot;
            beforeEach(() => {
                multiple = true;
            });

            beforeEach(() => {
                first_item = LazyboxItemStub.withDefaults({ value: "value-0" });
                second_item = LazyboxItemStub.withDefaults({ value: "value-1" });
                target = doc.createElement("div") as unknown as ShadowRoot;
            });

            it(`when the badge dispatches "remove-badge" event,
                the item will be removed from selection
                and it will dispatch an "open-dropdown" event`, () => {
                const host = getHost(first_item, second_item);
                const onSelection = vi.spyOn(host, "onSelection");
                const dispatch = vi.spyOn(host, "dispatchEvent");

                const render_functions = buildSelectedBadges(host);
                expect(render_functions).toHaveLength(2);
                render_functions[0](host, target);
                target.firstElementChild?.dispatchEvent(new CustomEvent("remove-badge"));

                expect(onSelection).toHaveBeenCalledWith([second_item.value]);
                expect(dispatch.mock.calls[0][0].type).toBe("open-dropdown");
            });
        });
    });

    describe(`selection methods`, () => {
        let multiple: boolean, is_selected: boolean;
        beforeEach(() => {
            multiple = true;
            is_selected = false;
        });

        const getHost = (...selected_items: LazyboxItem[]): HostElement => {
            return {
                multiple,
                selected_items,
                isSelected: (item) => {
                    return item ? is_selected : is_selected;
                },
                onSelection: noopOnSelection,
            } as HostElement;
        };

        describe(`replaceSelection()`, () => {
            it(`replaces the previous selection by the new one`, () => {
                const host = getHost(
                    LazyboxItemStub.withDefaults({ value: "value-1" }),
                    LazyboxItemStub.withDefaults({ value: "value-2" })
                );
                const onSelection = vi.spyOn(host, "onSelection");

                const new_item_1 = LazyboxItemStub.withDefaults({ value: "value-3" });
                const new_item_2 = LazyboxItemStub.withDefaults({ value: "value-4" });

                buildReplaceSelection(host)([new_item_1, new_item_2]);

                expect(host.selected_items).toHaveLength(2);
                expect(host.selected_items).toContain(new_item_1);
                expect(host.selected_items).toContain(new_item_2);
                expect(onSelection).toHaveBeenCalledWith([new_item_1.value, new_item_2.value]);
            });
        });

        describe("selectItem()", () => {
            it.each([
                ["when it is already selected", { value: "value-0" }, true],
                ["when it is disabled", { is_disabled: true }, false],
            ])("should not select the item %s", (when, item_partial, item_is_selected) => {
                is_selected = item_is_selected;
                const selected_item = LazyboxItemStub.withDefaults(item_partial);
                const host = getHost(selected_item);
                const onSelection = vi.spyOn(host, "onSelection");

                buildSelectItem(host)(selected_item);

                expect(onSelection).not.toHaveBeenCalled();
            });

            it(`when multiple selection is disabled,
                it should select the item and call the onSelection callback with its value`, () => {
                multiple = false;
                const host = getHost(LazyboxItemStub.withDefaults({ value: "value-0" }));
                const onSelection = vi.spyOn(host, "onSelection");
                const new_selection = LazyboxItemStub.withDefaults({ value: "value-1" });

                buildSelectItem(host)(new_selection);

                expect(onSelection).toHaveBeenCalledWith(new_selection.value);
                expect(host.selected_items).toHaveLength(1);
            });

            it(`when multiple selection is allowed,
                it should add the item to the selection
                and call the onSelection callback with all selected values`, () => {
                const previous_value = { id: 451 };
                const new_value = { id: 958 };
                const host = getHost(LazyboxItemStub.withDefaults({ value: previous_value }));
                const onSelection = vi.spyOn(host, "onSelection");
                const new_selection = LazyboxItemStub.withDefaults({ value: new_value });

                buildSelectItem(host)(new_selection);

                expect(host.selected_items).toHaveLength(2);
                expect(onSelection).toHaveBeenCalledWith([previous_value, new_value]);
            });
        });

        describe(`isSelected()`, () => {
            it(`returns false when an item is not selected`, () => {
                const host = getHost();
                const item = LazyboxItemStub.withDefaults();

                expect(buildIsSelected(host)(item)).toBe(false);
            });

            it(`returns true when an item is selected`, () => {
                const item = LazyboxItemStub.withDefaults();
                const host = getHost(item);

                expect(buildIsSelected(host)(item)).toBe(true);
            });
        });

        describe("clearSelection()", () => {
            it(`when multiple selection is disabled,
                it should clear the selection and call the onSelection callback with a null value`, () => {
                multiple = false;
                const host = getHost(LazyboxItemStub.withDefaults());
                const onSelection = vi.spyOn(host, "onSelection");

                buildClear(host)();

                expect(host.selected_items).toHaveLength(0);
                expect(onSelection).toHaveBeenCalledWith(null);
            });

            it(`when multiple selection is allowed,
                it should clear the selection and call the onSelection callback with an empty array`, () => {
                const host = getHost(LazyboxItemStub.withDefaults());
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
            const first_item = LazyboxItemStub.withDefaults({ value: { id: 0 } });
            const second_item = LazyboxItemStub.withDefaults({ value: { id: 1 } });
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

    describe("observeSelectedItems()", () => {
        const SEARCH_INPUT_PLACEHOLDER = "puzzle";
        let multiple: boolean;
        beforeEach(() => {
            multiple = true;
        });

        const getHost = (): SelectionElement => {
            const search_input = { placeholder: SEARCH_INPUT_PLACEHOLDER } as SearchInput;
            return {
                multiple,
                placeholder_text: PLACEHOLDER_TEXT,
                search_input,
            } as SelectionElement;
        };

        it(`will do nothing when multiple selection is disabled`, () => {
            multiple = false;
            const host = getHost();

            observeSelectedItems(host, []);

            expect(host.search_input.placeholder).toBe(SEARCH_INPUT_PLACEHOLDER);
        });

        describe(`when multiple selection is allowed`, () => {
            beforeEach(() => {
                multiple = true;
            });

            it(`and when the new selection is not empty,
                it will clear the search input placeholder`, () => {
                const host = getHost();

                observeSelectedItems(host, [LazyboxItemStub.withDefaults()]);

                expect(host.search_input.placeholder).toBe("");
            });

            it(`and when the new selection is empty,
                it will assign the search input placeholder`, () => {
                const host = getHost();

                observeSelectedItems(host, []);

                expect(host.search_input.placeholder).toBe(PLACEHOLDER_TEXT);
            });
        });
    });
});
