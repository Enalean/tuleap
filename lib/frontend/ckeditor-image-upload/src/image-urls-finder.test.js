/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { findImageUrls, isThereAnImageWithDataURI } from "./image-urls-finder.js";

describe(`image-urls-finder`, () => {
    describe(`findImageUrls()`, () => {
        it(`Given an HTML string, it will return an array of URLs extracted
            from the [src] attribute of all the img tags in it`, () => {
            const first_url = "https://example.com/unaccountability/advertently.jpg";
            const second_url = "http://example.com/sightlessly/hypersystole.png";
            const html_string = `<p>
                <img src="${first_url}">
                <img src="${second_url}">
            </p>`;

            const result = findImageUrls(html_string);

            expect(result).toEqual([first_url, second_url]);
        });

        it(`Given there isn't any image in the HTML string,
            it will return an empty array`, () => {
            const html_string = `<p></p>`;

            const result = findImageUrls(html_string);

            expect(result).toEqual([]);
        });
    });

    describe(`isThereAnImageWithDataURI()`, () => {
        it(`Given an HTML string containing an img tag with a [src] attribute
            with base-64 encoded image, it will return true`, () => {
            const html_string = `<p><img src="data:RWx2aXJh"></p>`;

            expect(isThereAnImageWithDataURI(html_string)).toBe(true);
        });

        it(`Given the image's [src] attribute
            was not a base-64 encoded image, it will return false`, () => {
            const html_string = `<p><img src="http://example.com/sightlessly/hypersystole.png"></p>`;

            expect(isThereAnImageWithDataURI(html_string)).toBe(false);
        });

        it(`Given an HTML string with no img tag, it will return false`, () => {
            const html_string = `<p>Lorem ipsum dolor sit amet</p>`;

            expect(isThereAnImageWithDataURI(html_string)).toBe(false);
        });
    });
});
