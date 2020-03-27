<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="dropdown tracker-colorpicker">
        <a
            class="dropdown-toggle"
            href="javascript:;"
            data-target="#"
            data-toggle="dropdown"
            v-on:click="showRightPalette"
        >
            <old-color-picker-preview v-if="show_old_preview" v-bind:color="color" />
            <color-picker-preview v-else v-bind:color="color" />
        </a>

        <div class="dropdown-menu" role="menu">
            <color-picker-palette
                v-if="!is_old_palette_shown"
                v-on:color-update="setColor"
                v-bind:current-color="color"
            />

            <old-color-picker-palette v-if="is_old_palette_shown" v-on:color-update="setColor" />

            <!-- Set transparent when clicked -->
            <p v-if="is_old_palette_shown" class="old-color-preview">
                <old-color-picker-preview
                    color
                    class="colorpicker-transparent-preview"
                    v-on:color-update="setColor"
                />
                <span class="old-colorpicker-no-color-label" v-on:click="setColor()" v-translate>
                    No color
                </span>
            </p>

            <color-picker-switch
                v-bind:is-switch-disabled="is_switch_disabled"
                v-bind:is-old-palette-shown="is_old_palette_shown"
                v-on:switch-palette="switchPalettes"
            />
        </div>
        <input
            class="colorpicker-input"
            v-bind:id="inputId"
            v-bind:name="inputName"
            v-bind:value="color"
            type="hidden"
            size="6"
            autocomplete="off"
        />
    </div>
</template>

<script>
import ColorPickerPalette from "./ColorPickerPalette.vue";
import ColorPickerSwitch from "./ColorPickerSwitch.vue";

import OldColorPickerPreview from "./OldColorPickerPreview.vue";
import OldColorPickerPalette from "./OldColorPickerPalette.vue";
import ColorPickerPreview from "./ColorPickerPreview.vue";

export default {
    name: "ColorPicker",
    components: {
        ColorPickerSwitch,
        ColorPickerPalette,
        ColorPickerPreview,
        OldColorPickerPalette,
        OldColorPickerPreview,
    },
    props: {
        inputName: String,
        inputId: String,
        currentColor: String,
        isSwitchDisabled: String,
    },
    data() {
        const is_hexa_color = this.currentColor.includes("#");
        const show_old_preview = this.currentColor.length === 0 || is_hexa_color;
        const is_switch_disabled = Boolean(this.isSwitchDisabled);
        const is_old_palette_shown = is_hexa_color && !is_switch_disabled;

        return {
            color: this.currentColor,
            is_old_palette_shown,
            show_old_preview,
            is_switch_disabled,
        };
    },
    computed: {
        isHexaColor() {
            return this.color.includes("#");
        },
    },
    methods: {
        setColor(color = "") {
            this.color = color;

            this.show_old_preview = !color.length || this.isHexaColor;
        },
        switchPalettes() {
            this.is_old_palette_shown = !this.is_old_palette_shown;
        },
        showRightPalette() {
            this.is_old_palette_shown = this.isHexaColor && !this.is_switch_disabled;
        },
    },
};
</script>
