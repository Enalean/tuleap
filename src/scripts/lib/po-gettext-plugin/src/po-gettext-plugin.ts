/**
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

import type { TransformResult } from "unplugin";
import { createUnplugin } from "unplugin";
import { dataToEsm } from "@rollup/pluginutils";
import { po } from "gettext-parser";

const plugin = createUnplugin(() => {
    return {
        name: "po-gettext",
        transformInclude(id: string): boolean {
            return id.endsWith(".po");
        },
        transform(source: string): TransformResult {
            return {
                code: dataToEsm(po.parse(source, "utf-8")),
            };
        },
    };
});

export default plugin;
