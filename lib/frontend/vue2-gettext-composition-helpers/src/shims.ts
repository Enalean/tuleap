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

// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Used to propagate the base interface
import type Vue from "vue/types/vue";

declare module "vue/types/vue" {
    interface Vue {
        $gettext: (msgid: string) => string;
        $pgettext: (context: string, msgid: string) => string;
        $ngettext: (msgid: string, plural: string, n: number) => string;
        $npgettext: (context: string, msgid: string, plural: string, n: number) => string;
        $gettextInterpolate: (
            msgid: string,
            context: object,
            disable_html_escaping?: boolean,
        ) => string;
    }
}
