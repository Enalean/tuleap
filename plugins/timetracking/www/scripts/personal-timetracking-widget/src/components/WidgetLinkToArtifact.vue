(<template>
    <a v-bind:href="artifact.html_url">
        <span class="tlp-badge-outline timetracking-badge-direct-link-to-artifact"
              v-bind:class="badge_color"
        >{{ artifact.xref }}</span>
        <span>{{ artifact.title }}</span>
    </a>
</template>)
(<script>
export default {
    name: "WidgetLinkToArtifact",
    props: {
        artifact: Object
    },
    computed: {
        badge_color() {
            return "tlp-badge-" + this.artifact.badge_color;
        }
    }
};
</script>)
