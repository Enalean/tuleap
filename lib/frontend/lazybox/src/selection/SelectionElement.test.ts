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
import type { UpdateFunction } from "hybrids";
import { selectOrThrow } from "@tuleap/dom";
import { RenderedItemStub } from "../../tests/stubs/RenderedItemStub";
import type { RenderedItem } from "../type";
import type { HostElement } from "./SelectionElement";
import {
    buildClear,
    buildHasSelection,
    buildSet,
    getClearSelectionButton,
    onKeyUp,
    selectedItemSetter,
    SelectionElement,
} from "./SelectionElement";

const noopOnSelection = (item: RenderedItem | null): void => {
    if (item !== null) {
        //Do nothing
    }
};

describe("SelectionElement", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("Rendering", () => {
        const getRenderedTemplate = (
            render_function: (host: HostElement) => UpdateFunction<HostElement>,
            host: HostElement
        ): ShadowRoot => {
            const render = render_function(host);
            const target = doc.createElement("div") as unknown as ShadowRoot;

            render(host, target);

            return target;
        };

        describe("getContent()", () => {
            it("When no item is selected, Then it should render a placeholder element containing the placeholder text", () => {
                const host = {
                    selected_item: undefined,
                    placeholder_text: "I'm holding the place 8-)",
                } as HostElement;

                const placeholder = selectOrThrow(
                    getRenderedTemplate(SelectionElement.content, host),
                    "[data-test=selection-placeholder]"
                );

                expect(placeholder.textContent?.trim()).toBe(host.placeholder_text);
            });

            it("When an item is selected, Then it should render it with its template", () => {
                const selected_item_element = doc.createElement("div") as HTMLElement;
                selected_item_element.setAttribute("data-test", "selected-element");

                const host = {
                    selected_item: RenderedItemStub.withDefaults(),
                    selected_item_element,
                } as HostElement;

                const content = selectOrThrow(
                    getRenderedTemplate(SelectionElement.content, host),
                    "[data-test=selected-element]"
                );

                expect(content).toBeDefined();
            });
        });

        describe("getClearSelectionButton()", () => {
            const getHost = (selected_item: RenderedItem | undefined): HostElement => {
                const host = doc.createElement("span") as HostElement;
                Object.assign(host, {
                    selected_item,
                    clearSelection: buildClear(host),
                    onSelection: noopOnSelection,
                });
                return host;
            };

            const getButton = (host: HostElement): HTMLButtonElement =>
                selectOrThrow(
                    getRenderedTemplate(getClearSelectionButton, host),
                    "[data-test=clear-current-selection-button]",
                    HTMLButtonElement
                );

            it("When no item is selected, Then it should not render the button", () => {
                const host = getHost(undefined);

                const empty_element = getRenderedTemplate(getClearSelectionButton, host);

                expect(empty_element.children).toHaveLength(0);
            });

            it(`When I click on the button, it will dispatch a "clear-selection" event
                and will call onSelection() with a null parameter`, () => {
                const host = getHost(RenderedItemStub.withDefaults());
                const dispatch = vi.spyOn(host, "dispatchEvent");
                const onSelection = vi.spyOn(host, "onSelection");

                const button = getButton(host);
                button.click();

                expect(dispatch.mock.calls[0][0].type).toBe("clear-selection");
                expect(host.selected_item).toBeUndefined();
                expect(onSelection).toHaveBeenCalledWith(null);
            });

            it(`when I press enter on the button, it will simulate a click on keyup instead of keydown
                so that enter keyup event is NOT dispatched in the open dropdown, which would select
                the first possible value immediately`, () => {
                const host = getHost(RenderedItemStub.withDefaults());
                const dispatch = vi.spyOn(host, "dispatchEvent");
                const onSelection = vi.spyOn(host, "onSelection");

                const button = getButton(host);
                const down_event = new KeyboardEvent("keydown", { key: "Enter", cancelable: true });
                const up_event = new KeyboardEvent("keyup", { key: "Enter" });
                button.dispatchEvent(down_event);
                button.dispatchEvent(up_event);

                expect(down_event.defaultPrevented).toBe(true);
                expect(dispatch.mock.calls[0][0].type).toBe("clear-selection");
                expect(host.selected_item).toBeUndefined();
                expect(onSelection).toHaveBeenCalledWith(null);
            });
        });
    });

    describe(`events`, () => {
        it(`when I press the "enter" key while focusing the selection,
            it will dispatch an "enter-pressed" event`, () => {
            const host = doc.createElement("span") as HostElement;
            const dispatch = vi.spyOn(host, "dispatchEvent");

            onKeyUp(host, new KeyboardEvent("keyup", { key: "Enter" }));

            expect(dispatch.mock.calls[0][0].type).toBe("enter-pressed");
        });
    });

    describe("selectItem()", () => {
        it.each([
            ["when it is already selected", { is_selected: true }],
            ["when it is disabled", { is_disabled: true }],
        ])("should not select the item %s", (when, item_partial) => {
            const selected_item = RenderedItemStub.withDefaults(item_partial);
            const host = {
                selected_item,
                onSelection: noopOnSelection,
            } as HostElement;
            const onSelection = vi.spyOn(host, "onSelection");

            buildSet(host)(selected_item);

            expect(onSelection).not.toHaveBeenCalled();
        });

        it("should select the item and call the onSelection callback", () => {
            const host = {
                selected_item: undefined,
                onSelection: noopOnSelection,
            } as HostElement;
            const onSelection = vi.spyOn(host, "onSelection");
            const new_selection = RenderedItemStub.withDefaults();

            buildSet(host)(new_selection);

            expect(onSelection).toHaveBeenCalledWith(new_selection.value);
        });
    });

    describe("clearSelection()", () => {
        it("should clear the selection and call the onSelection callback with a null value", () => {
            const host = {
                selected_item: RenderedItemStub.withDefaults(),
                onSelection: noopOnSelection,
            } as HostElement;
            const onSelection = vi.spyOn(host, "onSelection");

            buildClear(host)();

            expect(host.selected_item).toBeUndefined();
            expect(onSelection).toHaveBeenCalled();
        });
    });

    describe("hasSelection()", () => {
        it("should return false when no item is selected", () => {
            const host = {
                selected_item: undefined,
            } as HostElement;

            expect(buildHasSelection(host)()).toBe(false);
        });

        it("should return true when an item is selected", () => {
            const host = {
                selected_item: RenderedItemStub.withDefaults(),
            } as HostElement;

            expect(buildHasSelection(host)()).toBe(true);
        });
    });

    describe("selected item setter", () => {
        let host: SelectionElement;
        beforeEach(() => {
            host = {} as SelectionElement;
        });

        it(`will do nothing when new value is the same as old value`, () => {
            const old_value = RenderedItemStub.withDefaults({ is_selected: false });

            const return_value = selectedItemSetter(host, old_value, old_value);

            expect(return_value).toBe(old_value);
            expect(old_value.is_selected).toBe(false);
            expect(old_value.element.getAttribute("aria-selected")).toBe("false");
        });

        it(`will mark new value as selected in the dropdown`, () => {
            const new_value = RenderedItemStub.withDefaults({ is_selected: false });

            const return_value = selectedItemSetter(host, new_value, undefined);

            expect(return_value).toBe(new_value);
            expect(new_value.is_selected).toBe(true);
            expect(new_value.element.getAttribute("aria-selected")).toBe("true");
        });

        it(`will mark old value as NOT selected in the dropdown`, () => {
            const old_value = RenderedItemStub.withDefaults({ is_selected: true });
            old_value.element.setAttribute("aria-selected", "true");

            const return_value = selectedItemSetter(host, undefined, old_value);

            expect(return_value).toBeUndefined();
            expect(old_value.is_selected).toBe(false);
            expect(old_value.element.getAttribute("aria-selected")).toBe("false");
        });
    });
});
