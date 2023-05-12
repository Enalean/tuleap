/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { isLazyboxInAModal } from "../helpers/lazybox-in-modals-helper";

interface ScrollCoordinates {
    x_position: number;
    y_position: number;
}

export class ScrollingManager {
    private readonly first_scrollable_parent: HTMLElement | null = null;
    private readonly lock_parent_scrolling!: () => void;
    private readonly lock_window_scrolling!: () => void;
    private parent_scroll: ScrollCoordinates = { x_position: 0, y_position: 0 };
    private window_scroll: ScrollCoordinates = { x_position: 0, y_position: 0 };

    constructor(private readonly wrapper_element: HTMLElement) {
        this.first_scrollable_parent = this.findLazyboxFirstScrollableParent(this.wrapper_element);

        this.lock_parent_scrolling = (): void => {
            if (!this.first_scrollable_parent) {
                throw new Error("Can't lock scrolling if there is no scrollable parent");
            }

            this.first_scrollable_parent.scroll(
                this.parent_scroll.x_position,
                this.parent_scroll.y_position
            );
        };

        this.lock_window_scrolling = (): void => {
            window.scroll(this.window_scroll.x_position, this.window_scroll.y_position);
        };
    }

    public lockScrolling(): void {
        if (isLazyboxInAModal(this.wrapper_element)) {
            this.window_scroll = { x_position: window.scrollX, y_position: window.scrollY };
            window.addEventListener("scroll", this.lock_window_scrolling);
        }

        if (this.first_scrollable_parent === null) {
            return;
        }

        this.parent_scroll = {
            x_position: this.first_scrollable_parent.scrollLeft,
            y_position: this.first_scrollable_parent.scrollTop,
        };

        this.first_scrollable_parent.addEventListener("scroll", this.lock_parent_scrolling);
    }

    public unlockScrolling(): void {
        window.removeEventListener("scroll", this.lock_window_scrolling);
        if (this.first_scrollable_parent === null) {
            return;
        }
        this.first_scrollable_parent.removeEventListener("scroll", this.lock_parent_scrolling);
    }

    public findLazyboxFirstScrollableParent(wrapper_element: HTMLElement): HTMLElement | null {
        const parent_node = wrapper_element.parentNode;
        if (parent_node === null) {
            return null;
        }

        return this.findScrollableParent(parent_node);
    }

    private findScrollableParent(parent_node: Node): HTMLElement | null {
        if (this.isNodeScrollable(parent_node) && parent_node instanceof HTMLElement) {
            return parent_node;
        }

        if (parent_node.parentNode === null) {
            return null;
        }

        return this.findScrollableParent(parent_node.parentNode);
    }

    private isNodeScrollable(node: Node): boolean {
        if (!(node instanceof HTMLElement)) {
            return false;
        }

        const styles = window.getComputedStyle(node, null);
        const is_scrolling_on_any_axis = this.isAScrollableValue(
            styles.getPropertyValue("overflow")
        );
        const is_scrolling_on_y_axis = this.isAScrollableValue(
            styles.getPropertyValue("overflow-y")
        );
        const is_scrolling_on_x_axis = this.isAScrollableValue(
            styles.getPropertyValue("overflow-x")
        );

        return is_scrolling_on_any_axis || is_scrolling_on_y_axis || is_scrolling_on_x_axis;
    }

    private isAScrollableValue(value: string): boolean {
        return value === "scroll" || value === "auto";
    }
}
