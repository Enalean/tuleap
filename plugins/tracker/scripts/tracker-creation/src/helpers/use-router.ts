/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import type { Route } from "vue-router";
import { getCurrentInstance } from "vue";
import type VueRouter from "vue-router";

export function useRoute(): Route {
    const instance = getCurrentInstance();
    if (!instance) {
        throw Error("useRoute must be called in setup script");
    }

    return instance.proxy.$route;
}

export function useRouter(): VueRouter {
    const instance = getCurrentInstance();
    if (!instance) {
        throw Error("useRouter must be called in setup script");
    }

    return instance.proxy.$router;
}
