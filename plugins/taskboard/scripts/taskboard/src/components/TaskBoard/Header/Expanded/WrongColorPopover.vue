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
  -
  -->
<template>
    <div class="taskboard-header-wrong-color-container">
        <span
            class="taskboard-header-wrong-color"
            ref="trigger"
            data-placement="bottom"
            data-trigger="click"
        >
            <i class="fas fa-exclamation-triangle"></i>
        </span>
        <section
            class="tlp-popover tlp-popover-warning taskboard-header-wrong-color-popover"
            ref="container"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <translate tag="h1" class="tlp-popover-title">
                    Incompatible usage of color
                </translate>
            </div>
            <div class="tlp-popover-body taskboard-header-wrong-color-body">
                <p v-dompurify-html="legacy_palette_message"></p>
                <p v-translate>Only colors from the new palette can be used.</p>
                <p v-dompurify-html="adjust_configuration_message"></p>
            </div>
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Ref } from "vue-property-decorator";
import { createPopover } from "tlp";
import { State } from "vuex-class";

@Component
export default class TaskBoardHeaderCell extends Vue {
    @Prop({ required: true })
    readonly color!: string;

    @State
    readonly admin_url!: string;

    @Ref() trigger!: HTMLElement;
    @Ref() container!: HTMLElement;

    mounted(): void {
        if (this.trigger && this.container) {
            createPopover(this.trigger, this.container);
        }
    }

    get legacy_palette_message(): string {
        return this.$gettextInterpolate(
            this.$gettext(
                "The column is configured to use a color (%{ color }) from the legacy palette.",
            ),
            {
                color: `<span class="taskboard-header-wrong-color-preview"><span class="taskboard-header-wrong-color-preview-color" style="background: ${this.color};"></span>
                <code>${this.color}</code></span>`,
            },
            true,
        );
    }

    get adjust_configuration_message(): string {
        return this.$gettextInterpolate(
            this.$gettext(
                'Please <a href="%{ admin_url }">adjust configuration</a> to use a suitable color.',
            ),
            { admin_url: this.admin_url },
        );
    }
}
</script>
