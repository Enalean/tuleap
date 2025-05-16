/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import type { createGettext } from "vue3-gettext";

declare module "*.vue" {
    import type { DefineComponent } from "vue";
    const component: DefineComponent;
    export default component;
}

declare module "*.docx" {
    const value: string;
    export default value;
}

declare module "*.pptx" {
    const value: string;
    export default value;
}

declare module "*.xlsx" {
    const value: string;
    export default value;
}

type Language = ReturnType<typeof createGettext>;
declare module "vue" {
    interface ComponentCustomProperties
        extends Pick<Language, "$gettext" | "$pgettext" | "$ngettext" | "$npgettext"> {
        $gettextInterpolate: Language["interpolate"];
    }
}
