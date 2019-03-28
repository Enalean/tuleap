<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <a
        v-bind:class="buttonClasses"
        v-on:click="goToUpdate"
        v-if="item.user_can_write"
        data-test="docman-item-update-button"
    >
        <i v-bind:class="iconClasses"></i>
        <translate>Update</translate>
    </a>
</template>
<script>
import { mapState } from "vuex";
import { TYPE_EMPTY, TYPE_FILE, TYPE_WIKI, TYPE_EMBEDDED } from "../../../constants.js";

import { redirect_to_url } from "../../../helpers/location-helper.js";
export default {
    name: "UpdatButton",
    props: {
        item: Object,
        buttonClasses: String,
        iconClasses: String
    },
    computed: {
        ...mapState(["project_id"])
    },
    methods: {
        goToUpdate() {
            if (this.item.type === TYPE_FILE || this.item.type === TYPE_EMBEDDED) {
                this.showUpdateFileModal();
                return;
            }
            const action =
                this.item.type !== TYPE_WIKI && this.item.type !== TYPE_EMPTY
                    ? "action_new_version"
                    : "action_update";

            redirect_to_url(
                `/plugins/docman/index.php?group_id=${this.project_id}&id=${
                    this.item.id
                }&action=${action}`
            );
        },
        showUpdateFileModal() {
            let event_name;

            switch (this.item.type) {
                case TYPE_FILE:
                    event_name = "show-update-file-modal";
                    break;
                case TYPE_EMBEDDED:
                    event_name = "show-update-embedded-file-modal";
                    break;
            }

            document.dispatchEvent(
                new CustomEvent(event_name, {
                    detail: { current_item: this.item }
                })
            );
        }
    }
};
</script>
