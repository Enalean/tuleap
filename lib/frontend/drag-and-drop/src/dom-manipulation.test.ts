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
import {
    cloneHTMLElement,
    findClosestDraggable,
    findClosestDropzone,
    findNextGhostSibling,
    insertAfter,
} from "./dom-manipulation";
import type { DrekkenovInitOptions } from "./types";

describe(`dom-manipulation`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
    });

    describe(`cloneHTMLElement()`, () => {
        it(`clones an HTML Element and set its type to HTMLElement so TypeScript is happy`, () => {
            const div = doc.createElement("div");

            const clone = cloneHTMLElement(div);

            expect(clone.isEqualNode(div)).toBe(true);
        });
    });

    describe(`findClosestDraggable()`, () => {
        it(`when element is not an HTMLElement and has no parent, it will return null`, () => {
            const options = {} as DrekkenovInitOptions;
            const element = doc.createTextNode("I am a text node");

            const closest_draggable = findClosestDraggable(options, element);

            expect(closest_draggable).toBeNull();
        });

        it(`when element is an HTMLElement
            but is not a draggable and has no parent,
            it will return null`, () => {
            const options = {
                isDraggable() {
                    return false;
                },
            } as unknown as DrekkenovInitOptions;
            const element = doc.createElement("div");

            const closest_draggable = findClosestDraggable(options, element);

            expect(closest_draggable).toBeNull();
        });

        it(`when element is an HTMLElement
            and is a draggable,
            it will return element`, () => {
            const options = {
                isDraggable() {
                    return true;
                },
            } as unknown as DrekkenovInitOptions;

            const element = doc.createElement("div");

            const closest_draggable = findClosestDraggable(options, element);

            expect(closest_draggable).toBe(element);
        });

        it(`when element's parent is an HTMLElement and is a draggable, it will return the parent`, () => {
            const parent = doc.createElement("div");
            const child = doc.createElement("div");
            parent.append(child);
            const options = {
                isDraggable(element: HTMLElement): boolean {
                    return element === parent;
                },
            } as unknown as DrekkenovInitOptions;

            const closest_draggable = findClosestDraggable(options, child);

            expect(closest_draggable).toBe(parent);
        });

        it(`when element's grandparent is an HTMLElement and is a draggable, it will return the grandparent`, () => {
            const grandpa = doc.createElement("div");
            const parent = doc.createElement("div");
            const child = doc.createElement("div");
            parent.append(child);
            grandpa.append(parent);
            const options = {
                isDraggable(element: HTMLElement): boolean {
                    return element === grandpa;
                },
            } as unknown as DrekkenovInitOptions;

            const closest_draggable = findClosestDraggable(options, child);

            expect(closest_draggable).toBe(grandpa);
        });
    });

    describe(`findClosestDropzone()`, () => {
        it(`when element is not an HTMLElement and has no parent, it will return null`, () => {
            const options = {
                isDropZone() {
                    return false;
                },
            } as unknown as DrekkenovInitOptions;
            const element = doc.createTextNode("I am a text node");

            const closest_dropzone = findClosestDropzone(options, element);

            expect(closest_dropzone).toBeNull();
        });

        it(`when element is an HTMLElement but not a dropzone and has no parent,
            it will return null`, () => {
            const options = {
                isDropZone() {
                    return false;
                },
            } as unknown as DrekkenovInitOptions;
            const element = doc.createElement("div");

            const closest_dropzone = findClosestDropzone(options, element);

            expect(closest_dropzone).toBeNull();
        });

        it(`when element is an HTMLElement and is a dropzone, it will return element`, () => {
            const options = {
                isDropZone() {
                    return true;
                },
            } as unknown as DrekkenovInitOptions;
            const element = doc.createElement("div");

            const closest_dropzone = findClosestDropzone(options, element);

            expect(closest_dropzone).toBe(element);
        });

        it(`when element's parent is an HTMLElement and is a dropzone, it will return the parent`, () => {
            const parent = doc.createElement("div");
            const child = doc.createElement("div");
            parent.append(child);
            const options = {
                isDropZone(element: HTMLElement): boolean {
                    return element === parent;
                },
            } as DrekkenovInitOptions;

            const closest_dropzone = findClosestDropzone(options, child);

            expect(closest_dropzone).toBe(parent);
        });

        it(`when element's grandparent is an HTMLElement and is a dropzone, it will return the grandparent`, () => {
            const grandpa = doc.createElement("div");
            const parent = doc.createElement("div");
            const child = doc.createElement("div");
            parent.append(child);
            grandpa.append(child);
            const options = {
                isDropZone(element: HTMLElement): boolean {
                    return element === grandpa;
                },
            } as DrekkenovInitOptions;

            const closest_dropzone = findClosestDropzone(options, child);

            expect(closest_dropzone).toBe(grandpa);
        });
    });

    describe(`findNextGhostSibling()`, () => {
        it(`when children is empty,
            it will return null because that case is already handled by the caller`, () => {
            const closest_element = findNextGhostSibling(100, []);

            expect(closest_element).toBeNull();
        });

        it(`when the first child's middle y coordinate is > y_coordinate,
            it will return the first child so that drop ghost is inserted before it`, () => {
            const children = createChildrenWithRects(doc, 101, 151, 201);

            const closest_element = findNextGhostSibling(100, children);

            expect(closest_element).toBe(children[0]);
        });

        it(`when the second child's middle y coordinate is > y_coordinate,
            it will return the second child so that drop ghost is inserted before it`, () => {
            const children = createChildrenWithRects(doc, 101, 151, 201);

            const closest_element = findNextGhostSibling(150, children);

            expect(closest_element).toBe(children[1]);
        });

        it(`when the last child's middle y coordinate is > y_coordinate,
            it will return the last child so that drop ghost is inserted before it`, () => {
            const children = createChildrenWithRects(doc, 101, 151, 201);

            const closest_element = findNextGhostSibling(200, children);

            expect(closest_element).toBe(children[2]);
        });

        it(`when all of the children's middle y coordinates are <= y_coordinate,
            it will return null so that drop ghost is inserted after the last child`, () => {
            const children = createChildrenWithRects(doc, 101, 151, 201);

            const closest_element = findNextGhostSibling(201, children);

            expect(closest_element).toBeNull();
        });
    });

    describe(`insertAfter()`, () => {
        it(`inserts the drop ghost in dropzone's children, after reference_element`, () => {
            const dropzone = doc.createElement("div");
            const drop_ghost = doc.createElement("div");
            const reference_element = doc.createElement("div");
            dropzone.append(reference_element);

            insertAfter(dropzone, drop_ghost, reference_element);

            expect(drop_ghost.parentElement).toBe(dropzone);
            expect(reference_element.nextElementSibling).toBe(drop_ghost);
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function createChildrenWithRects(
    doc: Document,
    first_middle_coordinate: number,
    second_middle_coordinate: number,
    third_middle_coordinate: number,
): Element[] {
    const first_child = doc.createElement("div");
    const first_rect = {
        top: first_middle_coordinate - 25,
        bottom: first_middle_coordinate + 25,
    } as DOMRect;
    vi.spyOn(first_child, "getBoundingClientRect").mockReturnValue(first_rect);
    const second_child = doc.createElement("div");
    const second_rect = {
        top: second_middle_coordinate - 25,
        bottom: second_middle_coordinate + 25,
    } as DOMRect;
    vi.spyOn(second_child, "getBoundingClientRect").mockReturnValue(second_rect);
    const third_child = doc.createElement("div");
    const third_rect = {
        top: third_middle_coordinate - 25,
        bottom: third_middle_coordinate + 25,
    } as DOMRect;
    vi.spyOn(third_child, "getBoundingClientRect").mockReturnValue(third_rect);
    return [first_child, second_child, third_child];
}
