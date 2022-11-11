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

export const add_modal_mixin = {
    props: {
        project_id: {
            type: String,
            required: true,
        },
    },
    computed: {
        form_url() {
            return `/project/${encodeURIComponent(this.project_id)}/admin/services/add`;
        },
        preview_label() {
            return this.service.label === "" ? this.$gettext("Preview") : this.service.label;
        },
    },
    data() {
        return {
            is_shown: false,
            service: this.resetService(),
        };
    },
    methods: {
        show() {
            this.is_shown = true;
            this.$refs.modal.show();
        },
        resetModal() {
            this.is_shown = false;
            this.service = this.resetService();
        },
        resetService() {
            return {
                id: null,
                icon_name: "fa-angle-double-right",
                label: "",
                link: "",
                description: "",
                short_name: "",
                is_active: true,
                is_used: true,
                is_in_new_tab: false,
                rank: this.minimal_rank,
                is_disabled_reason: "",
            };
        },
    },
};
