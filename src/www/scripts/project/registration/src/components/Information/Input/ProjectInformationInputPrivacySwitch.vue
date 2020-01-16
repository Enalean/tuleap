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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-form-element">
        <label class="tlp-label" for="project-information-input-privacy-switch-label">
            <span v-translate>Privacy</span>
            <i
                class="fa fa-question-circle project-information-input-privacy-icon"
                aria-hidden="true"
                data-placement="top"
                ref="trigger"
            ></i>
        </label>
        <section class="tlp-popover" ref="container">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title" v-translate>Information about privacy</h1>
            </div>
            <div class="tlp-popover-body">
                <p data-test="project-information-input-privacy-text">
                    {{ translated_tooltip }}
                </p>
            </div>
        </section>
        <div class="tlp-switch tlp-switch-large">
            <input
                type="checkbox"
                id="project-information-input-privacy-switch-label"
                class="tlp-switch-checkbox project-information-input-privacy-switch-checkbox"
                v-model="is_checked"
                v-on:click="$emit('input', !is_checked)"
                data-test="project-information-input-privacy-switch"
            />
            <label
                for="project-information-input-privacy-switch-label"
                class="tlp-switch-button project-information-input-privacy-switch-button"
                aria-hidden
            ></label>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import { createPopover } from "tlp";
import Vue from "vue";
import { State } from "vuex-class";

@Component
export default class ProjectInformationInputPrivacySwitch extends Vue {
    is_checked = false;

    @State
    are_anonymous_allowed!: boolean;

    mounted(): void {
        const trigger = this.$refs.trigger;
        const container = this.$refs.container;
        if (trigger instanceof Element && container instanceof Element) {
            createPopover(trigger, container);
        }
    }

    get translated_tooltip(): string {
        if (this.is_checked) {
            return this.$gettext(
                "Project privacy set to private. Only project members can access its content."
            );
        }
        if (this.are_anonymous_allowed) {
            return this.$gettext(
                "Project privacy set to public. By default, its content is available to everyone (authenticated or not). Please note that more restrictive permissions might exist on some items."
            );
        }
        return this.$gettext(
            "Project privacy set to public. By default, its content is available to all authenticated. Please note that more restrictive permissions might exist on some items."
        );
    }
}
</script>
