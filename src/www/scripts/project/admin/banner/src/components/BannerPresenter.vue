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
            <label class="tlp-label" for="toggle" v-translate>Activate banner</label>
            <div class="tlp-switch">
                <input
                    type="checkbox"
                    id="toggle"
                    class="tlp-switch-checkbox"
                    v-on:click="switchBannerActivation"
                    v-model="banner_is_activated"
                >
                <label
                    for="toggle"
                    class="tlp-switch-button"
                    aria-hidden
                ></label>
            </div>
        </div>
        <div v-if="!banner_is_activated">
            <p v-translate>No banner defined</p>
        </div>
        <div v-else>
            <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled' : loading }">
                <label class="tlp-label" for="description" v-translate>Message</label>
                <textarea type="text" class="tlp-textarea" id="description" name="description" v-model="current_message" v-bind:placeholder="$gettext('Choose a banner message')"></textarea>
            </div>
        </div>
        <div class="tlp-pane-section-submit">
            <button type="button" class="tlp-button-primary" v-on:click="save" v-bind:disabled="loading">
                <i v-if="loading" class="tlp-button-icon fa fa-fw fa-spin fa-circle fa-circle-o-notch"></i>
                <i class="tlp-button-icon fa fa-save"></i>
                &nbsp;
                <span v-translate>Save</span>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BannerState } from "../type";
@Component
export default class BannerPresenter extends Vue {
    @Prop({ required: true, type: String })
    readonly message!: string;

    @Prop({ required: true, type: Boolean })
    readonly loading!: boolean;

    banner_is_activated: boolean = this.message !== "";
    current_message: string = this.message;

    public switchBannerActivation(): void {
        this.banner_is_activated = !this.banner_is_activated;
    }

    public save(): void {
        if (this.current_message.length === 0 && this.banner_is_activated) {
            return;
        }

        const banner_save_payload: BannerState = {
            message: this.current_message,
            activated: this.banner_is_activated
        };

        this.$emit("save-banner", banner_save_payload);
    }
}
</script>
