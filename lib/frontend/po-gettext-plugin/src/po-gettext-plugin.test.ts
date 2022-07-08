/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

/**
 * @vitest-environment node
 */

import { describe, it, expect } from "vitest";
import plugin from "./po-gettext-plugin";
import type { UnpluginOptions } from "unplugin";

describe("plugin-po-gettext", () => {
    describe("transformIncludes", () => {
        it("transforms .po files", () => {
            expect(getPlugin().transformInclude("fr_FR.po")).toBe(true);
        });

        it("does not transform other files", () => {
            expect(getPlugin().transformInclude("A.vue")).not.toBe(true);
        });
    });

    describe("transform", () => {
        it("converts .PO files", () => {
            const po_file_source = `msgid "Cancel"
msgstr "Annuler"`;
            expect(getPlugin().transform(po_file_source)).toMatchSnapshot();
        });
    });
});

interface ExpectedPlugin {
    transformInclude: Exclude<UnpluginOptions["transformInclude"], undefined>;
    transform: (source: string) => { code: string };
}

function getPlugin(): ExpectedPlugin {
    return plugin.raw(undefined, { framework: "vite" }) as unknown as ExpectedPlugin;
}
