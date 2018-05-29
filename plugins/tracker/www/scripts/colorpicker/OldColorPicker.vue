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
            <old-color-picker-preview v-bind:color="color" />
        </a>
        <div class="dropdown-menu" role="menu" aria-labelledby="dLabel">
            <old-color-picker-palette v-on:color-update="setColor"/>

            <!-- Set transparent when clicked -->
            <old-color-picker-preview color
                class="colorpicker-transparent-preview"
                v-on:color-update="setColor"
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
    import OldColorPickerPalette from "./OldColorPickerPalette.vue";
    import OldColorPickerPreview from "./OldColorPickerPreview.vue";

    export default {
        name: "OldColorPicker",
        components: { OldColorPickerPreview, OldColorPickerPalette },
        props: {
            decoratorId: String,
            valueId    : String,
            colorHexa  : String
        },
        data() {
            return {
                color: this.colorHexa
            };
        },
        methods: {
            setColor(color) {
                this.color = color;
            }
        }
    }
</script>
