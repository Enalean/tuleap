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

import { RelativeDateElement } from "./relative-date-element";

describe("relative-date element", () => {
    let date_in_the_past: Date;
    let absolute_date: string;

    function createRelativeDateElement(placement: string, preference: string): RelativeDateElement {
        const doc = document.implementation.createHTMLDocument();
        const container = document.createElement("div");

        // eslint-disable-next-line no-unsanitized/property
        container.innerHTML = `<tlp-relative-date
                    date="${date_in_the_past.toISOString()}"
                    absolute-date="${absolute_date}"
                    placement="${placement}"
                    preference="${preference}"
                    locale="en_US"
                ></tlp-relative-date>`;

        const tlp_local_time = container.querySelector("tlp-relative-date");
        if (!(tlp_local_time instanceof RelativeDateElement)) {
            throw Error("Unable to find just created element");
        }

        doc.body.appendChild(container);

        return tlp_local_time;
    }

    beforeAll(() => {
        window.customElements.define("tlp-relative-date", RelativeDateElement);
    });

    beforeEach(() => {
        date_in_the_past = new Date();
        date_in_the_past.setDate(date_in_the_past.getDate() - 5);

        absolute_date = new Intl.DateTimeFormat("en-US", {
            year: "numeric",
            month: "numeric",
            day: "numeric",
            hour: "numeric",
            minute: "numeric",
            hour12: false,
        }).format(date_in_the_past);
    });

    describe("when user prefers absolute dates", () => {
        it("displays absolute date as text content and relative one as title which will be displayed on the right", () => {
            const tlp_local_time = createRelativeDateElement("right", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.getAttribute("title")).toBe("5 days ago");
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(true);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });

        it("displays absolute date as text content and relative one as title which will be displayed on top", () => {
            const tlp_local_time = createRelativeDateElement("top", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.getAttribute("title")).toBe("5 days ago");
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(true);
        });

        it("displays absolute date as text content and relative one as title which will be displayed as tooltip", () => {
            const tlp_local_time = createRelativeDateElement("tooltip", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.getAttribute("title")).toBe("5 days ago");
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });
    });

    describe("when user prefers relative dates", () => {
        it("displays relative date as text content and absolute one as title which will be displayed on the right", () => {
            const tlp_local_time = createRelativeDateElement("right", "relative");

            expect(tlp_local_time.textContent).toBe("5 days ago");
            expect(tlp_local_time.getAttribute("title")).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(true);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });

        it("displays relative date as text content and absolute one as title which will be displayed on top", () => {
            const tlp_local_time = createRelativeDateElement("top", "relative");

            expect(tlp_local_time.textContent).toBe("5 days ago");
            expect(tlp_local_time.getAttribute("title")).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(true);
        });

        it("displays relative date as text content and absolute one as title which will be displayed as tooltip", () => {
            const tlp_local_time = createRelativeDateElement("tooltip", "relative");

            expect(tlp_local_time.textContent).toBe("5 days ago");
            expect(tlp_local_time.getAttribute("title")).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });
    });

    describe("updates the display when", () => {
        it("the preference changes", () => {
            const tlp_local_time = createRelativeDateElement("tooltip", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);

            tlp_local_time.preference = "relative";

            expect(tlp_local_time.textContent).toBe("5 days ago");
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });

        it("the placement changes", () => {
            const tlp_local_time = createRelativeDateElement("tooltip", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);

            tlp_local_time.placement = "right";

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(true);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });

        it("the placement is 'tooltip'", () => {
            const tlp_local_time = createRelativeDateElement("right", "absolute");

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(true);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);

            tlp_local_time.placement = "tooltip";

            expect(tlp_local_time.textContent).toBe(absolute_date);
            expect(tlp_local_time.classList.contains("tlp-date-on-right")).toBe(false);
            expect(tlp_local_time.classList.contains("tlp-date-on-top")).toBe(false);
        });
    });
});
