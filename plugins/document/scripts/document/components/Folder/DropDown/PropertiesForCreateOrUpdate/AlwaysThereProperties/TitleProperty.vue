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
    <div
        class="tlp-form-element document-title-property"
        v-bind:class="{ 'tlp-form-element-disabled': is_root_folder }"
        data-test="document-new-item-title-form-element"
    >
        <label class="tlp-label" for="document-title">
            <translate>Title</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            id="document-title"
            name="title"
            v-bind:placeholder="`${$gettext('My document')}`"
            required
            v-bind:value="value"
            v-bind:disabled="is_root_folder"
            v-on:input="oninput"
            ref="input"
            data-test="document-new-item-title"
        />
        <p class="tlp-text-danger" v-if="error_message.length > 0" data-test="title-error-message">
            {{ error_message }}
        </p>
    </div>
</template>
<script lang="ts">
import {
    doesDocumentAlreadyExistsAtUpdate,
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
    doesFolderAlreadyExistsAtUpdate,
} from "../../../../../helpers/properties-helpers/check-item-title";
import { isFolder } from "../../../../../helpers/type-check-helper";
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import type { Folder, Item } from "../../../../../type";
import { State } from "vuex-class";
import emitter from "../../../../../helpers/emitter";

@Component
export default class TitleProperty extends Vue {
    @Prop({ required: true })
    readonly value!: string;

    @Prop({ required: true })
    readonly parent!: Folder;

    @Prop({ required: true })
    readonly isInUpdateContext!: boolean;

    @Prop({ required: true })
    readonly currentlyUpdatedItem!: Item;

    @State
    readonly folder_content!: Array<Item>;

    private error_message = "";

    get is_root_folder(): boolean {
        return this.currentlyUpdatedItem.parent_id === 0;
    }

    @Watch("value")
    public updateValue(text_value: string): void {
        const error = this.checkTitleValidity(text_value);
        if (this.$refs.input instanceof HTMLInputElement) {
            this.$refs.input.setCustomValidity(error);
        }
        this.error_message = error;
    }

    mounted(): void {
        if (this.$refs.input instanceof HTMLInputElement) {
            this.$refs.input.focus();
        }
    }

    checkTitleValidity(text_value: string): string {
        if (this.isInUpdateContext) {
            return this.getValidityErrorAtUpdate(text_value);
        }
        return this.getValidityErrorAtCreation(text_value);
    }

    getValidityErrorAtCreation(text_value: string): string {
        if (
            isFolder(this.currentlyUpdatedItem) &&
            doesFolderNameAlreadyExist(text_value, this.folder_content, this.parent)
        ) {
            return this.getErrorWhenFolderAlreadyExists();
        } else if (
            !isFolder(this.currentlyUpdatedItem) &&
            doesDocumentNameAlreadyExist(text_value, this.folder_content, this.parent)
        ) {
            return this.getErrorWhenDocumentAlreadyExists();
        }

        return "";
    }

    getValidityErrorAtUpdate(text_value: string): string {
        if (
            !isFolder(this.currentlyUpdatedItem) &&
            doesDocumentAlreadyExistsAtUpdate(
                text_value,
                this.folder_content,
                this.currentlyUpdatedItem,
                this.parent
            )
        ) {
            return this.getErrorWhenDocumentAlreadyExists();
        } else if (
            isFolder(this.currentlyUpdatedItem) &&
            doesFolderAlreadyExistsAtUpdate(
                text_value,
                this.folder_content,
                this.currentlyUpdatedItem,
                this.parent
            )
        ) {
            return this.getErrorWhenFolderAlreadyExists();
        }

        return "";
    }

    getErrorWhenDocumentAlreadyExists(): string {
        return this.$gettext("A document already exists with the same title.");
    }

    getErrorWhenFolderAlreadyExists(): string {
        return this.$gettext("A folder already exists with the same title.");
    }

    oninput($event: Event): void {
        if ($event.target instanceof HTMLInputElement) {
            emitter.emit("update-title-property", $event.target.value);
        }
    }
}
</script>
