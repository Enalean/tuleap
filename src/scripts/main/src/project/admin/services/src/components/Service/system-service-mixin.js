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

import { ADMIN_SERVICE_SHORTNAME, SUMMARY_SERVICE_SHORTNAME } from "../../constants.js";

export const system_service_mixin = {
    computed: {
        is_summary_service() {
            return this.service.short_name === SUMMARY_SERVICE_SHORTNAME;
        },
        can_update_is_used() {
            return this.service.short_name !== ADMIN_SERVICE_SHORTNAME || !this.service.is_used;
        },
    },
};
