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

<!--
This component is a wrapper around tlp select2.
/!\ Limitation: only int values are allowed.
/!\ Data have to be passed in slot with <option/>.
Do not use data attribute in configuration.
-->
<template>
    <select v-bind:disabled="disabled">
        <slot />
    </select>
</template>
<script>
import { select2 } from "tlp";

export default {
    name: "MultiSelect",
    props: {
        value: {
            // only ints are allowed here
            type: Array,
            default: () => [],
        },
        configuration: {
            // select2 configuration
            type: Object,
            default: () => {
                return {};
            },
        },
        disabled: {
            type: Boolean,
            default: () => false,
        },
    },
    data() {
        return {
            select2_control: null,
        };
    },
    watch: {
        value(value) {
            this.select2_control.val(this.convertToStrings(value)).trigger("change");
        },
    },
    mounted() {
        this.select2_control = select2(this.$el, { ...this.configuration, multiple: true })
            .val(this.convertToStrings(this.value))
            .trigger("change")
            .on("change", this.onChange);
    },
    destroyed() {
        this.select2_control.off().select2("destroy");
    },
    methods: {
        // Used to convert values returned by select2 before emitting
        // these values to parent in expected type.
        // This is required as select works only with strings.
        // See https://select2.org/data-sources/formats#automatic-string-casting
        convertToInts(values) {
            if (!values) {
                return values;
            }
            return values.map((value) => Number(value));
        },
        // Used to converted received values to select2
        convertToStrings(values) {
            if (!values) {
                return values;
            }
            return values.map((value) => value.toString());
        },
        onChange() {
            const new_value = this.convertToInts(this.select2_control.val());
            // This is required to avoid infinite loop if each emitted input
            // event are listen to trigger a new value update (with VueX for example).
            if (this.areArrayEquals(this.value, new_value)) {
                return;
            }
            this.$emit("input", new_value);
        },
        areArrayEquals(values1, values2) {
            if (values1 === values2) {
                return true;
            }
            if (values1 === null || values2 === null || values1.length !== values2.length) {
                return false;
            }
            if (values1.length === 0) {
                return true;
            }
            return values1.every((value1, index) => values2[index] === value1);
        },
    },
};
</script>
