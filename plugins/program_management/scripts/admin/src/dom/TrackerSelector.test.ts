/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { DocumentAdapter } from "./DocumentAdapter";
import { TrackerSelector } from "./TrackerSelector";

describe(`TrackerSelector`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`hasSelection`, () => {
        it(`returns true when an option is selected`, () => {
            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<select id="select">
                      <option value=""></option>
                      <option value="101" selected></option>
                      <option value="126"></option>
                  </select>`,
            );
            const selector = TrackerSelector.fromId(new DocumentAdapter(doc), "select");

            expect(selector.hasSelection()).toBe(true);
        });

        it(`returns false when the empty option is selected`, () => {
            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<select id="select">
                      <option value="" selected></option>
                      <option value="101"></option>
                      <option value="126"></option>
                </select>`,
            );
            const selector = TrackerSelector.fromId(new DocumentAdapter(doc), "select");

            expect(selector.hasSelection()).toBe(false);
        });
    });

    describe(`addChangeListener`, () => {
        it(`Given a non-empty option was selected,
            when the option is unselected,
            it will call the callback with false`, () => {
            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<select id="select">
                      <option value=""></option>
                      <option value="101" selected></option>
                      <option value="126"></option>
                  </select>`,
            );
            const retriever = new DocumentAdapter(doc);
            const select_element = retriever.getSelectById("select");
            const selector = TrackerSelector.fromId(retriever, "select");

            const callback = jest.fn();
            selector.addChangeListener(callback);
            select_element.selectedIndex = -1;
            select_element.dispatchEvent(new Event("change"));

            expect(callback).toHaveBeenCalledWith(false);
        });

        it(`Given the empty option was selected,
            when another option is selected,
            it will call the callback with true`, () => {
            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<select id="select">
                      <option value="" selected></option>
                      <option value="101"></option>
                      <option value="126"></option>
                </select>`,
            );
            const retriever = new DocumentAdapter(doc);
            const select_element = retriever.getSelectById("select");
            const selector = TrackerSelector.fromId(retriever, "select");

            const callback = jest.fn();
            selector.addChangeListener(callback);
            select_element.selectedIndex = 2;
            select_element.dispatchEvent(new Event("change"));

            expect(callback).toHaveBeenCalledWith(true);
        });
    });
});
