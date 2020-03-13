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

import { getSlugifiedShortname } from "./shortname-slugifier";

describe("shortname slugifier", () => {
    it("Returns a lowercased shortname with slugs in lieu of special chars", () => {
        const name = `User stories`;

        expect(getSlugifiedShortname(name)).toEqual("user_stories");
    });

    it("When the tracker name is more than 25 chars, then it slugifies the 25 first ones", () => {
        const name = "A wonderfully handsome tracker (and it is mine)";
        const shortname = getSlugifiedShortname(name);

        expect(shortname.length).toEqual(25);
        expect(shortname).toEqual("a_wonderfully_handsome_tr");
    });

    it("slugifies special chars", () => {
        const name = `+a.a~a(a)a!a:a@a"a'a`;

        expect(getSlugifiedShortname(name)).toEqual("_a_a_a_a_a_a_a_a_a_a");
    });

    it("slugifies other special chars", () => {
        const name = `*a©a®a-a<a>a`;

        expect(getSlugifiedShortname(name)).toEqual("_a_a_a_a_a_a");
    });

    it("truncates when its longer than 25 characters", () => {
        const name = `+a.a~a(a)a!a:a@a"a'a*a©a®a-a<a>a`;

        expect(getSlugifiedShortname(name)).toEqual("_a_a_a_a_a_a_a_a_a_a_a_a_");
    });

    it("only slugify once when special characters are sibilings", () => {
        const name = `"'*©®-<>`;

        expect(getSlugifiedShortname(name)).toEqual("_");
    });
});
