/**
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

import formatRelativeDate from "./relative-date-formatter";

const allowed_placements = ["top", "right", "tooltip"];
const allowed_preferences = ["absolute", "relative"];

export class RelativeDateElement extends HTMLElement {
    constructor() {
        super();
    }

    public connectedCallback(): void {
        if (!this.locale) {
            throw new Error("Missing required attribute locale for tlp-relative-date component");
        }

        if (!this.date) {
            throw new Error("Missing required attribute `date` for tlp-relative-date component");
        }

        if (!this.absolute_date) {
            throw new Error(
                "Missing required attribute `absolute-date` for tlp-relative-date component"
            );
        }

        if (!allowed_preferences.includes(this.preference)) {
            throw new Error(
                "Missing required attribute `preference` (absolute|relative) for tlp-relative-date component"
            );
        }

        if (!allowed_placements.includes(this.placement)) {
            throw new Error(
                "Missing required attribute `placement` (top|right|tooltip) for tlp-relative-date component"
            );
        }

        const date = new Date(this.date);

        if (this.preference === "absolute") {
            this.textContent = this.absolute_date;
            this.setTitle(formatRelativeDate(this.locale, date, new Date()));
        } else {
            this.textContent = formatRelativeDate(this.locale, date, new Date());
            this.setTitle(this.absolute_date);
        }
        this.setClassNameAccordingToPlacement();
    }

    get placement(): string {
        return this.getAttribute("placement") ?? "";
    }

    set placement(value: string) {
        if (allowed_placements.includes(value)) {
            this.setAttribute("placement", value);
        }
    }

    get preference(): string {
        return this.getAttribute("preference") ?? "";
    }

    set preference(value: string) {
        if (allowed_preferences.includes(value)) {
            this.setAttribute("preference", value);
        }
    }

    get date(): string {
        return this.getAttribute("date") ?? "";
    }

    set date(value: string) {
        this.setAttribute("date", value);
    }

    get absolute_date(): string {
        return this.getAttribute("absolute-date") ?? "";
    }

    set absolute_date(value: string) {
        this.setAttribute("absolute_date", value);
    }

    get locale(): string {
        const locale = this.getAttribute("locale") ?? "";

        return locale.replace("_", "-");
    }

    set locale(value: string) {
        this.setAttribute("locale", value);
    }

    private setTitle(title: string): void {
        if (!this.hasAttribute("title")) {
            this.setAttribute("title", title);
        }
    }

    private setClassNameAccordingToPlacement(): void {
        if (this.placement === "right") {
            this.classList.add("tlp-date-on-right");
        } else if (this.placement === "top") {
            this.classList.add("tlp-date-on-top");
        }
    }
}
