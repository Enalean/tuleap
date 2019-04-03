<template>
    <span class="baseline-artifact-badge tlp-badge-outline"
          v-bind:class="tlp_badge_class"
    >
        {{ artifact.tracker.item_name }} #{{ artifact.id }}
    </span>
</template>
<script>
export default {
    name: "ArtifactBadge",
    props: {
        artifact: {
            required: true,
            type: Object
        }
    },
    computed: {
        tlp_badge_class() {
            const normalized_color = this.artifact.tracker.color_name.replace("_", "-");
            return `tlp-badge-${normalized_color}`;
        }
    }
};
</script>
