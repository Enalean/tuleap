/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

import Vue from "vue";

export default {
    namespaced: true,
    state: {
        notification: null,
        modal: null,
    },
    mutations: {
        notify: (state, message) => (state.notification = message),
        clearNotification: (state) => (state.notification = null),
        showModal: (state, modal) =>
            // Vue.extend() is required here to prevent store mutation when given component is mounted
            // (which is a bad practice, identified when strict mode is enabled)
            (state.modal = { ...modal, component: Vue.extend(modal.component) }),
        hideModal: (state) => (state.modal = null),
    },
};
