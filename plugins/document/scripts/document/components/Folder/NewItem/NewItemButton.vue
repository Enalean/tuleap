<!--
  - Copyright (c) Enalean, 2018 - 2018. All Rights Reserved.
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
  -
  -->

<template>
    <div>
        <button
            type="button"
            class="tlp-button-primary"
            v-on:click="showNewDocumentModal()"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            <translate>New document</translate>
        </button>
        <new-item-modal ref="modal"/>
    </div>
</template>

<script>
import NewItemModal from "./NewItemModal.vue";
import { modal as createModal } from "tlp";

export default {
    name: "EmptyFolder",
    components: { NewItemModal },
    data() {
        return {
            modal: null
        };
    },
    mounted() {
        this.modal = createModal(this.$refs.modal.$el);
    },
    methods: {
        showNewDocumentModal() {
            this.modal.show();
        }
    }
};
</script>
