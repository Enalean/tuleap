<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled': loading }">
            <label class="tlp-label tlp-checkbox"
                ><input type="checkbox" v-model="banner_is_activated" />{{
                    $gettext("Activate the banner on this project")
                }}</label
            >
        </div>
        <div v-show="banner_is_activated">
            <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled': loading }">
                <label class="tlp-label" for="description">{{ $gettext("Message") }}</label>
                <textarea
                    ref="embedded_editor"
                    class="tlp-textarea"
                    id="description"
                    name="description"
                    v-model="current_message"
                    v-bind:placeholder="$gettext('Choose a banner message')"
                ></textarea>
                <p class="tlp-text-muted">
                    {{ $gettext("Your message will be condensed to one line") }}
                </p>
            </div>
        </div>
        <div class="tlp-pane-section-submit">
            <button
                type="button"
                class="tlp-button-primary"
                v-bind:data-tlp-tooltip="$gettext('Message is mandatory')"
                v-bind:class="{ 'tlp-tooltip tlp-tooltip-top': should_tooltip_be_displayed }"
                v-on:click="save"
                v-bind:disabled="is_save_button_disabled"
            >
                <i
                    v-if="loading"
                    class="tlp-button-icon fa fa-fw fa-spin fa-circle fa-circle-o-notch"
                ></i>
                <i v-if="!loading" class="tlp-button-icon fa fa-save"></i
                >{{ $gettext("Save the configuration") }}
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { BannerState } from "../type";
import "ckeditor4";

@Component
export default class BannerPresenter extends Vue {
    @Prop({ required: true, type: String })
    readonly message!: string;

    @Prop({ required: true, type: Boolean })
    readonly loading!: boolean;

    banner_is_activated: boolean = this.message !== "";
    current_message: string = this.message;
    // eslint-disable-next-line no-undef
    editor: CKEDITOR.editor | null = null;

    get should_tooltip_be_displayed(): boolean {
        return this.current_message.length === 0 && this.banner_is_activated && !this.loading;
    }

    get is_save_button_disabled(): boolean {
        return (this.current_message.length === 0 && this.banner_is_activated) || this.loading;
    }

    public mounted(): void {
        this.createEditor();
    }

    public beforeDestroy(): void {
        this.destroyEditor();
    }

    private createEditor(): void {
        this.destroyEditor();

        const text_area = this.$refs.embedded_editor;
        if (!(text_area instanceof HTMLTextAreaElement)) {
            throw new Error("The ref embedded_editor is not a HTMLTextAreaElement");
        }

        // eslint-disable-next-line no-undef
        this.editor = CKEDITOR.replace(text_area, {
            toolbar: [
                ["Cut", "Copy", "Paste", "Undo", "Redo", "Link", "Unlink"],
                ["Bold", "Italic"],
            ],
            disableNativeSpellChecker: false,
            linkShowTargetTab: false,
        });

        this.editor.on("instanceReady", this.onInstanceReady);
    }

    private onInstanceReady(): void {
        if (this.editor === null) {
            return;
        }

        this.editor.on("change", this.onChange);

        this.editor.on("mode", () => {
            if (this.editor === null) {
                return;
            }

            if (this.editor.mode === "source") {
                const editable = this.editor.editable();
                editable.attachListener(editable, "input", () => {
                    this.onChange();
                });
            }
        });
    }

    private onChange(): void {
        if (this.editor === null) {
            return;
        }

        this.current_message = this.editor.getData();
    }

    private destroyEditor(): void {
        if (this.editor !== null) {
            this.editor.destroy();
            this.editor = null;
        }
    }

    public save(): void {
        if (this.current_message.length === 0 && this.banner_is_activated) {
            return;
        }

        const banner_save_payload: BannerState = {
            message: this.current_message,
            activated: this.banner_is_activated,
        };

        this.$emit("save-banner", banner_save_payload);
    }
}
</script>
