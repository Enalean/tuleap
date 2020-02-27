<template>
    <div class="tlp-form-element" v-on:click="toggleManualMode()">
        <label
            class="tlp-label"
            data-test="tracker-shortname-slugified-mode"
            id="tracker-shortname-slugified-mode-label"
            for="tracker-shortname-slugified"
        >
            <translate>Tracker shortname:</translate>
            <span data-test="tracker-shortname-slugified" class="tracker-shortname-slugified">
                {{ tracker_to_be_created.shortname }}
            </span>
            <i class="fa fa-pencil"></i>
        </label>
        <input
            type="hidden"
            id="tracker-shortname-slugified"
            class="tlp-input"
            data-test="tracker-shortname-slugified-input"
            name="tracker-shortname"
            v-bind:value="tracker_to_be_created.shortname"
        />
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { State, Mutation } from "vuex-class";
import { Component } from "vue-property-decorator";
import { TrackerToBeCreatedMandatoryData } from "../../../../store/type";

@Component({
    components: {}
})
export default class FieldShortnameSlugified extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    @Mutation
    readonly setSlugifyShortnameMode!: (is_active: boolean) => void;

    toggleManualMode(): void {
        this.setSlugifyShortnameMode(false);
    }
}
</script>
