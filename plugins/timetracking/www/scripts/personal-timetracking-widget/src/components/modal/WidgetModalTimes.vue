(<template>
    <div v-bind:id="new_id" class="tlp-modal" role="dialog">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                <i class="fa fa-pencil tlp-modal-title-icon"></i>
                {{ edit_time }}
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">
                &times;
            </div>
        </div>
        <widget-modal-content
            v-bind:time-data="timeData"
            v-bind:total-time="totalTime"
        />
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button type="button" class="tlp-button-primary tlp-button-outline tlp-modal-action" data-dismiss="modal">
                {{ close_label }}
            </button>
        </div>
    </div>
</template>)
(<script>
import { gettext_provider } from "../../gettext-provider.js";
import WidgetModalContent from "./WidgetModalContent.vue";
export default {
    name: "WidgetModalTimes",
    components: { WidgetModalContent },
    props: {
        timeData: Array,
        totalTime: String
    },
    data() {
        return {
            artifact: this.timeData[0].artifact
        };
    },
    computed: {
        close_label: () => gettext_provider.gettext("Close"),
        edit_time: () => gettext_provider.gettext("Detailed times"),
        new_id() {
            return "timetracking-artifact-details-modal-" + this.artifact.id;
        }
    }
};
</script>)
