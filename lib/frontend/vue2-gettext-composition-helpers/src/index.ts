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

import { getCurrentInstance } from "vue";
export type * from "./shims";

interface Language {
    $gettext: (msgid: string) => string;
    $pgettext: (context: string, msgid: string) => string;
    $ngettext: (msgid: string, plural: string, n: number) => string;
    $npgettext: (context: string, msgid: string, plural: string, n: number) => string;
    interpolate: (msgid: string, context: object, disable_html_escaping?: boolean) => string;
}

export function useGettext(): Language {
    const vm = getCurrentInstance();
    if (vm === null) {
        throw new Error(
            "You must use this function within the `setup()` method or a <script setup> tag",
        );
    }

    return {
        $gettext: vm.proxy.$gettext,
        $pgettext: vm.proxy.$pgettext,
        $ngettext: vm.proxy.$ngettext,
        $npgettext: vm.proxy.$npgettext,
        interpolate: vm.proxy.$gettextInterpolate,
    };
}
