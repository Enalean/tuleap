/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import mitt from "mitt";
import type { TroveCatProperties } from "../type";
export type Events = {
    "update-project-visibility": { new_visibility: string };
    "update-field-list": { field_id: string; value: string };
    "choose-trove-cat": TroveCatProperties;
    "update-project-name": { slugified_name: string; name: string };
    "slugify-project-name": string;
    "show-agreement": void;
};
export default mitt<Events>();
