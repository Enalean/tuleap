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
    <div class="tlp-framed">
        <div class="document-header">
            <div class="embedded-document-header-title">
                <h1>{{ embedded_title }}</h1>
            </div>

            <actions-header v-bind:item="embedded_file"/>
        </div>

        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-html="embedded_content"></section>
            </div>
        </section>

        <update-embedded-file-modal v-bind:is="shown_modal" v-bind:item="embedded_file"/>
    </div>
</template>

<script>
import dompurify from "dompurify";
import DropdownButton from "../ActionsDropDown/DropdownButton.vue";
import DropdownMenu from "../ActionsDropDown/DropdownMenu.vue";
import UpdateItemButton from "../ActionsButton/UpdateItemButton.vue";
import UpdateEmbeddedFileModal from "../ModalUpdateItem/UpdateEmbeddedFileModal.vue";
import ActionsHeader from "./ActionsHeader.vue";
export default {
    name: "DisplayEmbeddedContent",
    components: {
        ActionsHeader,
        UpdateEmbeddedFileModal,
        DropdownMenu,
        UpdateItemButton,
        DropdownButton
    },
    props: {
        embedded_file: Object
    },
    data() {
        return {
            shown_modal: ""
        };
    },
    computed: {
        embedded_title() {
            return this.embedded_file.title;
        },
        embedded_content() {
            if (!this.embedded_file.embedded_file_properties) {
                return;
            }

            return dompurify.sanitize(this.embedded_file.embedded_file_properties.content);
        }
    },
    mounted() {
        document.addEventListener("show-update-item-modal", this.showUpdateItemModal);

        this.$once("hook:beforeDestroy", () => {
            document.removeEventListener("show-update-item-modal", this.showUpdateItemModal);
        });
    },
    methods: {
        showUpdateItemModal() {
            this.shown_modal = () =>
                import(/* webpackChunkName: "document-update-embedded-file-modal" */ "../ModalUpdateItem/UpdateEmbeddedFileModal.vue");
        }
    }
};
</script>
