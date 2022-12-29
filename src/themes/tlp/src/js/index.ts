/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

export * from "@tuleap/tlp-popovers";
export * from "@tuleap/tlp-modal";

export { default as locale } from "./default_locale";

import jQuery from "jquery";
// Many scripts still depend on jQuery being on window
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.jQuery = jQuery;

export * from "../vendor-overrides/select2";
export * from "../vendor-overrides/flatpickr";
