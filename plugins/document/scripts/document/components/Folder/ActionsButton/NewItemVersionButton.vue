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
        v-if="item.user_can_write"
        v-bind:class="button_classes"
        v-bind:data-tlp-tooltip="cannot_create_new_wiki_version_because_approval_table"
        v-on:click="goToUpdate"
        data-test="document-new-item-version-button"
    >
        <i v-bind:class="iconClasses"></i>
        <translate>Create new version</translate>
    </a>
</template>
<script>
import { TYPE_WIKI } from "../../../constants.js";
import EventBus from "../../../helpers/event-bus.js";

export default {
    name: "NewItemVersionButton",
    props: {
        item: Object,
        buttonClasses: String,
        iconClasses: String
    },
    computed: {
        is_item_a_wiki_with_approval_table() {
            return this.item.type === TYPE_WIKI && this.item.approval_table !== null;
        },
        cannot_create_new_wiki_version_because_approval_table() {
            return this.$gettext("This wiki has a approval table, you can't update it.");
        },
        button_classes() {
            let classes = this.buttonClasses;

            if (this.is_item_a_wiki_with_approval_table) {
                classes +=
                    " document-new-item-version-button-disabled tlp-tooltip tlp-tooltip-left";
            }

            return classes;
        }
    },
    methods: {
        goToUpdate() {
            if (this.is_item_a_wiki_with_approval_table) {
                return;
            }

            EventBus.$emit("show-create-new-item-version-modal", {
                detail: { current_item: this.item }
            });
        }
    }
};
</script>
