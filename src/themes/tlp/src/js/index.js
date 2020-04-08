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
// Those two deps are at tuleap root level
// eslint-disable-next-line import/no-extraneous-dependencies
import "core-js/stable";
// eslint-disable-next-line import/no-extraneous-dependencies
import "regenerator-runtime/runtime";

export * from "./fetch-wrapper.js";

export { default as locale } from "./default_locale.js";

export { default as modal } from "./modal.js";

export { default as dropdown } from "./dropdowns.js";

export { default as createPopover } from "./popovers.js";

export { default as filterInlineTable } from "./filter-inline-table.js";

import jQuery from "jquery";
// Many scripts still depend on jQuery being on window
window.jQuery = jQuery;

export { default as select2 } from "../vendor-overrides/select2.js";

export { default as datePicker } from "../vendor-overrides/flatpickr.js";
