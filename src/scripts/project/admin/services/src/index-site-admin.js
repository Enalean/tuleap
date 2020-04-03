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

import Vue from "vue";
import BaseSiteAdminAddModal from "./components/BaseSiteAdminAddModal.vue";
import BaseSiteAdminEditModal from "./components/BaseSiteAdminEditModal.vue";
import { buildCreateModalCallback } from "./vue-modal-initializer.js";
import { setupModalButtons } from "./modal-initializer.js";

document.addEventListener("DOMContentLoaded", () => {
    const add_mount_point = "service-add-modal";
    const AddModalRootComponent = Vue.extend(BaseSiteAdminAddModal);
    const addModalCallback = buildCreateModalCallback(add_mount_point, AddModalRootComponent);

    const edit_mount_point = "service-edit-modal";
    const EditModalRootComponent = Vue.extend(BaseSiteAdminEditModal);
    const editModalCallback = buildCreateModalCallback(edit_mount_point, EditModalRootComponent);

    setupModalButtons(addModalCallback, editModalCallback);
});
