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

type HTMLElementPredicate = (element: HTMLElement) => boolean;

export interface DragCallbackParameter {
    dragged_element: HTMLElement;
}
type DragHandlerCallback = (context: DragCallbackParameter) => void;

export interface DropCallbackParameter {
    target_dropzone: HTMLElement;
}

export type DragDropCallbackParameter = DragCallbackParameter & DropCallbackParameter;
type DragDropHandlerCallback = (context: DragDropCallbackParameter) => void;

export interface PossibleDropCallbackParameter {
    dragged_element: HTMLElement;
    source_dropzone: HTMLElement;
    target_dropzone: HTMLElement;
}
type PossibleDropPredicate = (context: PossibleDropCallbackParameter) => boolean;
type PossibleDropHandlerCallback = (context: PossibleDropCallbackParameter) => void;

export interface SuccessfulDropCallbackParameter {
    dropped_element: HTMLElement;
    source_dropzone: HTMLElement;
    target_dropzone: HTMLElement;
    next_sibling: Element | null;
}
type SuccessfulDropHandlerCallback = (context: SuccessfulDropCallbackParameter) => void;

export interface DrekkenovInitOptions {
    mirror_container: Element;
    isDraggable: HTMLElementPredicate;
    isDropZone: HTMLElementPredicate;
    isInvalidDragHandle: HTMLElementPredicate;
    isConsideredInDropzone: HTMLElementPredicate;
    doesDropzoneAcceptDraggable: PossibleDropPredicate;
    onDragStart: DragHandlerCallback;
    onDragEnter: PossibleDropHandlerCallback;
    onDragLeave: DragDropHandlerCallback;
    onDrop: SuccessfulDropHandlerCallback;
    cleanupAfterDragCallback(): void;
}

export interface Drekkenov {
    destroy(): void;
}

export type DragHandler = (event: DragEvent) => void;

export interface DragDropHandlers {
    dragEndHandler: DragHandler;
    dragEnterHandler: DragHandler;
    dragLeaveHandler: DragHandler;
    dragOverHandler: DragHandler;
    dropHandler: DragHandler;
}

export interface DragStartContext {
    readonly dragged_element: HTMLElement;
    readonly source_dropzone: HTMLElement;
    readonly initial_sibling: Element | null;
}

export interface AfterDropListener {
    afterDrop(): void;
}

export interface AfterDropEventSource {
    attachAfterDropListener(listener: AfterDropListener): void;
    dispatchAfterDropEvent(): void;
}
