/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { GHOST_CSS_CLASS, HIDE_CSS_CLASS } from "./constants";
import type { OngoingDrag } from "./OngoingDrag";
import { DropGhost } from "./DropGhost";
import type { AfterDropEventSource, DrekkenovInitOptions } from "./types";
import * as dom_manipulation from "./dom-manipulation";

describe(`DropGhost`, () => {
    let mock_event_source: AfterDropEventSource, doc: Document;

    beforeEach(() => {
        mock_event_source = {
            attachAfterDropListener: vi.fn(),
            dispatchAfterDropEvent: vi.fn(),
        };
        doc = createLocalDocument();
    });

    describe(`create()`, () => {
        let ongoing_drag: OngoingDrag;

        beforeEach(() => {
            ongoing_drag = createOngoingDrag(doc);
        });

        it(`clones the dragged element, removes the HIDE css class, adds the GHOST css class
            and returns a new DropGhost with the clone`, () => {
            const cloneElement = vi.spyOn(dom_manipulation, "cloneHTMLElement");
            DropGhost.create(mock_event_source, ongoing_drag);

            const clone = cloneElement.mock.results[0].value;
            expect(clone.classList.contains(HIDE_CSS_CLASS)).toBe(false);
            expect(clone.classList.contains(GHOST_CSS_CLASS)).toBe(true);
        });

        it(`will not touch the dragged_element's existing CSS classes`, () => {
            ongoing_drag.dragged_element.classList.add("custom-css-class");
            const cloneElement = vi.spyOn(dom_manipulation, "cloneHTMLElement");
            DropGhost.create(mock_event_source, ongoing_drag);

            const clone = cloneElement.mock.results[0].value;
            expect(ongoing_drag.dragged_element.classList.contains("custom-css-class")).toBe(true);
            expect(clone.classList.contains("custom-css-class")).toBe(true);
        });
    });

    describe(`constructor()`, () => {
        it(`attaches itself to the AfterDropEventSource parameter`, () => {
            const ongoing_drag = createOngoingDrag(doc);
            const element = doc.createElement("div");
            element.classList.add(GHOST_CSS_CLASS);

            const ghost = new DropGhost(mock_event_source, ongoing_drag, element);

            expect(mock_event_source.attachAfterDropListener).toHaveBeenCalledWith(ghost);
        });
    });

    describe(`ghost methods that do not depend on ongoing_drag`, () => {
        let ghost: DropGhost, ghost_element: HTMLElement;

        beforeEach(() => {
            const ongoing_drag = {} as OngoingDrag;
            ghost_element = doc.createElement("div");
            ghost_element.classList.add(GHOST_CSS_CLASS);
            ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
        });

        describe(`getSibling()`, () => {
            it(`when the ghost element has no sibling, it returns null`, () => {
                const dropzone = doc.createElement("div");
                dropzone.append(ghost_element);

                const sibling = ghost.getSibling();

                expect(sibling).toBeNull();
            });

            it(`when the ghost element has a sibling, it returns it`, () => {
                const dropzone = doc.createElement("div");
                const sibling_element = doc.createElement("div");
                dropzone.append(sibling_element);
                dropzone.insertBefore(ghost_element, sibling_element);

                const sibling = ghost.getSibling();

                expect(sibling).toBe(sibling_element);
            });
        });

        describe(`afterDrop()`, () => {
            it(`removes the ghost element`, () => {
                vi.spyOn(ghost_element, "remove");

                ghost.afterDrop();

                expect(ghost_element.remove).toHaveBeenCalled();
            });
        });
    });

    describe(`ghost methods that request animation frame`, () => {
        let ghost_element: HTMLElement;

        beforeEach(() => {
            ghost_element = doc.createElement("div");
            ghost_element.classList.add(GHOST_CSS_CLASS);
            vi.spyOn(window, "requestAnimationFrame").mockImplementation(
                (callback: FrameRequestCallback) => {
                    callback(0);
                    return 0;
                },
            );
        });

        describe(`update()`, () => {
            const y_coordinate = 200;
            let ongoing_drag: OngoingDrag, ghost: DropGhost, target_dropzone: HTMLElement;
            beforeEach(() => {
                const dragged_element = doc.createElement("div");
                dragged_element.classList.add(HIDE_CSS_CLASS);
                ongoing_drag = {
                    dragged_element,
                    hideDraggedElement: vi.fn(),
                } as unknown as OngoingDrag;
                ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
                target_dropzone = doc.createElement("div");
            });

            it(`will hide the dragged element`, () => {
                const options = {} as DrekkenovInitOptions;
                vi.spyOn(ongoing_drag, "hideDraggedElement");

                ghost.update(target_dropzone, options, y_coordinate);

                expect(ongoing_drag.hideDraggedElement).toHaveBeenCalledWith(options);
            });

            it(`when there are no children considered in target dropzone,
                it will prepend the ghost element in it`, () => {
                const ignored_element = doc.createElement("span");
                target_dropzone.append(ignored_element);
                const options = {
                    isConsideredInDropzone(element: HTMLElement): boolean {
                        return element !== ignored_element;
                    },
                } as DrekkenovInitOptions;

                ghost.update(target_dropzone, options, y_coordinate);

                expect(ghost_element.parentElement).toBe(target_dropzone);
                expect(ghost_element.nextElementSibling).toBe(ignored_element);
            });

            it(`when there are no children considered in target dropzone
                and the ghost element is already child of target dropzone,
                it will do nothing`, () => {
                const options = {
                    isConsideredInDropzone() {
                        return false;
                    },
                } as unknown as DrekkenovInitOptions;
                target_dropzone.append(ghost_element);

                ghost.update(target_dropzone, options, y_coordinate);

                expect(target_dropzone.children[0]).toBe(ghost_element);
            });

            it(`when some of the target dropzone's children are not considered by options callback,
                they will be filtered and ignored in the move()`, () => {
                const ignored_element = doc.createElement("span");
                const next_sibling = doc.createElement("div");
                const options = {
                    isConsideredInDropzone(element: HTMLElement): boolean {
                        return element !== ignored_element;
                    },
                } as DrekkenovInitOptions;
                target_dropzone.append(ignored_element, next_sibling);
                vi.spyOn(dom_manipulation, "findNextGhostSibling").mockReturnValue(next_sibling);

                ghost.update(target_dropzone, options, y_coordinate);

                expect(target_dropzone.children[1]).toBe(ghost_element);
            });

            describe(`when there are considered children`, () => {
                let first_child: HTMLElement,
                    last_child: HTMLElement,
                    options: DrekkenovInitOptions;

                beforeEach(() => {
                    options = {
                        isConsideredInDropzone() {
                            return true;
                        },
                    } as unknown as DrekkenovInitOptions;
                    first_child = doc.createElement("div");
                    last_child = doc.createElement("div");
                });

                it(`when the ghost element's next sibling is null,
                    it will insert the ghost element after the target dropzone's last child`, () => {
                    target_dropzone.append(first_child, last_child);
                    vi.spyOn(dom_manipulation, "findNextGhostSibling").mockReturnValue(null);

                    ghost.update(target_dropzone, options, y_coordinate);

                    expect(target_dropzone.children[2]).toEqual(ghost_element);
                });

                it(`when the ghost element's next sibling is null,
                    and the ghost element is already after the last child,
                    it will do nothing`, () => {
                    target_dropzone.append(first_child, last_child, ghost_element);
                    vi.spyOn(dom_manipulation, "findNextGhostSibling").mockReturnValue(null);

                    ghost.update(target_dropzone, options, y_coordinate);

                    expect(target_dropzone.children[2]).toBe(ghost_element);
                });

                it(`when there is a next sibling,
                    it will insert the ghost element before it in the target dropzone`, () => {
                    target_dropzone.append(first_child, last_child);
                    vi.spyOn(dom_manipulation, "findNextGhostSibling").mockReturnValue(last_child);

                    ghost.update(target_dropzone, options, y_coordinate);

                    expect(target_dropzone.children[1]).toBe(ghost_element);
                });

                it(`when there is a next sibling,
                    and the ghost element is already before it,
                    it will do nothing`, () => {
                    target_dropzone.append(first_child, ghost_element, last_child);
                    vi.spyOn(dom_manipulation, "findNextGhostSibling").mockReturnValue(last_child);

                    ghost.update(target_dropzone, options, y_coordinate);

                    expect(target_dropzone.children[1]).toBe(ghost_element);
                });
            });
        });

        describe(`revertAtInitialPlace()`, () => {
            it(`when the ghost element is already at the dragged element's initial place,
            it does nothing`, () => {
                const source_dropzone = doc.createElement("div");
                const initial_sibling = doc.createElement("span");
                source_dropzone.append(ghost_element, initial_sibling);
                const ongoing_drag = {
                    source_dropzone,
                    initial_sibling,
                } as unknown as OngoingDrag;
                const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);

                ghost.revertAtInitialPlace();

                expect(ghost_element.parentElement).toBe(source_dropzone);
            });

            it(`otherwise, it inserts the ghost element at the dragged element's initial place`, () => {
                const source_dropzone = doc.createElement("div");
                const initial_sibling = doc.createElement("span");
                source_dropzone.append(initial_sibling);
                const ongoing_drag = {
                    source_dropzone,
                    initial_sibling,
                } as unknown as OngoingDrag;
                const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);

                const other_dropzone = doc.createElement("div");
                other_dropzone.append(ghost_element);

                ghost.revertAtInitialPlace();

                expect(ghost_element.parentElement).toBe(source_dropzone);
                expect(ghost_element.nextElementSibling).toBe(initial_sibling);
            });
        });
    });

    describe(`isAtDraggedElementInitialPlace()`, () => {
        let ghost_element: HTMLElement, source_dropzone: HTMLElement, dragged_element: HTMLElement;

        beforeEach(() => {
            ghost_element = doc.createElement("div");
            ghost_element.classList.add(GHOST_CSS_CLASS);
            source_dropzone = doc.createElement("div");
            dragged_element = doc.createElement("div");
            dragged_element.classList.add(HIDE_CSS_CLASS);
        });

        it(`returns false when the source dropzone is not === the ghost element's parent`, () => {
            const initial_sibling = doc.createElement("div");
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling,
            } as unknown as OngoingDrag;
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            const other_dropzone = doc.createElement("div");
            other_dropzone.append(ghost_element);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(false);
        });

        it(`returns false when the initial sibling is null and the ghost element's next sibling is an element`, () => {
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling: null,
            } as unknown as OngoingDrag;
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            const ghost_sibling = doc.createElement("div");
            source_dropzone.append(ghost_element, ghost_sibling);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(false);
        });

        it(`returns true when the initial sibling is null and the ghost element's next sibling is also null`, () => {
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling: null,
            } as unknown as OngoingDrag;
            const unrelated_element = doc.createElement("span");
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            source_dropzone.append(unrelated_element, ghost_element);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(true);
        });

        it(`returns false when the initial sibling is an element and the ghost element's next sibling is null`, () => {
            const initial_sibling = doc.createElement("div");
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling,
            } as unknown as OngoingDrag;
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            source_dropzone.append(ghost_element);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(false);
        });

        it(`returns false when the initial sibling is not === the ghost element's next sibling`, () => {
            const initial_sibling = doc.createElement("div");
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling,
            } as unknown as OngoingDrag;
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            const ghost_sibling = doc.createElement("span");
            source_dropzone.append(ghost_element, ghost_sibling);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(false);
        });

        it(`returns true otherwise`, () => {
            const initial_sibling = doc.createElement("div");
            const ongoing_drag = {
                dragged_element,
                source_dropzone,
                initial_sibling,
            } as unknown as OngoingDrag;
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);
            source_dropzone.append(ghost_element, initial_sibling);

            expect(ghost.isAtDraggedElementInitialPlace()).toBe(true);
        });
    });

    describe(`contains()`, () => {
        let doc: Document, ongoing_drag: OngoingDrag, ghost_element: Element;
        beforeEach(() => {
            doc = createLocalDocument();
            ghost_element = doc.createElement("div");
            ongoing_drag = {} as OngoingDrag;
        });

        it(`returns true if the ghost element contains given node`, () => {
            const node = doc.createTextNode("I am inside ghost element");
            ghost_element.append(node);
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);

            expect(ghost.contains(node)).toBe(true);
        });

        it(`returns false if the ghost element does not contain given node`, () => {
            const node = doc.createTextNode("I am NOT inside ghost element");
            const ghost = new DropGhost(mock_event_source, ongoing_drag, ghost_element);

            expect(ghost.contains(node)).toBe(false);
        });
    });
});

function createOngoingDrag(doc: Document): OngoingDrag {
    const dragged_element = doc.createElement("div");
    dragged_element.classList.add(HIDE_CSS_CLASS);
    return {
        dragged_element,
    } as unknown as OngoingDrag;
}

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
