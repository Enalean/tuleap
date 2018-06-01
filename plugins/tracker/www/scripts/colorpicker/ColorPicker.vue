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
    <div class="dropdown tracker-old-colorpicker">
        <a class="dropdown-toggle"
           href="javascript:;"
           data-target="#"
           data-toggle="dropdown"
        >
            <old-color-picker-preview v-bind:color="color"/>
        </a>
        <div class="dropdown-menu" role="menu">
            <color-picker-palette v-if="! is_old_palette_shown" />

            <old-color-picker-palette v-if="is_old_palette_shown"
                v-on:color-update="setColor"
            />

            <!-- Set transparent when clicked -->
            <old-color-picker-preview v-if="is_old_palette_shown"
                color
                class="colorpicker-transparent-preview"
                v-on:color-update="setColor"
            />

            <color-picker-switch v-bind:is-old-palette-shown="is_old_palette_shown"
                v-on:switch-palette="switchPalettes"
                v-bind:switch-default-palette-label="switchDefaultPaletteLabel"
                v-bind:switch-old-palette-label="switchOldPaletteLabel"
            />
        </div>
        <input v-bind:id="decoratorId + '_field'"
               type="hidden"
               v-bind:name="'bind[decorator][' + valueId + ']'"
               v-bind:value="color"
        />
    </div>
</template>

<script>
    import ColorPickerPalette    from "./ColorPickerPalette.vue";
    import ColorPickerSwitch     from "./ColorPickerSwitch.vue";

    import OldColorPickerPreview from "./OldColorPickerPreview.vue";
    import OldColorPickerPalette from "./OldColorPickerPalette.vue";

    export default {
        name: "ColorPicker",
        components: {
            ColorPickerSwitch,
            ColorPickerPalette,
            OldColorPickerPalette,
            OldColorPickerPreview
        },
        props: {
            decoratorId                : String,
            valueId                    : String,
            colorHexa                  : String,
            switchDefaultPaletteLabel  : String,
            switchOldPaletteLabel      : String
        },
        data() {
            return {
                color               : this.colorHexa,
                is_old_palette_shown: true
            };
        },
        methods: {
            setColor(color) {
                this.color = color;
            },
            switchPalettes() {
                this.is_old_palette_shown = ! this.is_old_palette_shown;
            }
        }
    }
</script>
