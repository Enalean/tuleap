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
        <div class="tlp-modal-body timetracking-details-modal-content">
            <div class="tlp-pane-section timetracking-details-modal-artifact-title">
                <span class="tlp-badge-outline timetracking-badge-direct-link-to-artifact"
                      v-bind:class="badge_color"
                >
                    {{ artifact.xref }}
                </span>
                {{ artifact.title }}
            </div>
            <div class="timetracking-details-modal-artifact-details">
                <div class="timetracking-details-modal-artifact-infos-container">
                    <div class="timetracking-details-modal-artifact-infos">
                        <span>
                            {{ submission_label }}
                        </span>
                        <span>
                            {{ submission_date }}
                        </span>
                    </div>
                    <div class="timetracking-details-modal-artifact-infos">
                        <span>
                            <svg class="timetracking-details-project-icon" xmlns="http://www.w3.org/2000/svg" width="14"
                                 height="9" viewBox="0 0 14 9">
                                <path fill-rule="evenodd"
                                      d="M23.5318439,19.3542382 C23.5087512,19.2441277 23.5,19.1247307 23.5,19 C23.5,18.4477153 23.6715729,18 24.5,18 C25.3284271,18 25.5,18.4477153 25.5,19 C25.5,19.1247307 25.4912488,19.2441277 25.4681561,19.3542382 C25.7934549,19.6293899 26,20.0405744 26,20.5 C26,21.3284271 25.3284271,22 24.5,22 C23.6715729,22 23,21.3284271 23,20.5 C23,20.0405744 23.2065451,19.6293899 23.5318439,19.3542382 Z M36,24 L37,24 L37,25 L23,25 L23,24 L27,24 L27,16 L29,16 L30,16 L32,16 L32,20 L35,20 L36,20 L36,21 L36,24 Z M32,21 L32,22 L33,22 L33,21 L32,21 Z M34,21 L34,22 L35,22 L35,21 L34,21 Z M34,23 L34,24 L35,24 L35,23 L34,23 Z M32,23 L32,24 L33,24 L33,23 L32,23 Z M30,23 L30,24 L31,24 L31,23 L30,23 Z M30,21 L30,22 L31,22 L31,21 L30,21 Z M28,23 L28,24 L29,24 L29,23 L28,23 Z M28,21 L28,22 L29,22 L29,21 L28,21 Z M28,19 L28,20 L28.5,20 L29,20 L29,19 L28,19 Z M28,17 L28,18 L28.5,18 L29,18 L29,17 L28,17 Z M30,19 L30,20 L30.5,20 L31,20 L31,19 L30,19 Z M30,17 L30,18 L30.5,18 L31,18 L31,17 L30,17 Z M24,22 L25,22 L25,24 L24,24 L24,22 Z"
                                      transform="translate(-23 -16)"/>
                            </svg>{{ label_project }}
                        </span>
                        <span class="timetracking-details-modal-artifact-infos-project-name">
                            {{ project.label }}
                        </span>
                    </div>
                </div>
                <div class="timetracking-details-modal-artefact-link-top-bottom-spacer">
                    <a class="timetracking-badge-direct-link-to-artifact timetracking-edit-link"
                       v-bind:href="timetracking_url">
                        {{ edit_time }}
                    </a>
                </div>
                <table class="tlp-table">
                    <thead>
                    <tr>
                        <th>{{ date_message }}</th>
                        <th>{{ steps_label }}</th>
                        <th>{{ time_label }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <widget-modal-rows v-for="time in sorted_times"
                        v-bind:time-data="time"
                    />
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th class="tlp-table-last-row timetracking-total-sum">∑ {{ totalTime }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button type="button" class="tlp-button-primary tlp-button-outline tlp-modal-action" data-dismiss="modal">
                {{ close_label }}
            </button>
        </div>
    </div>
</template>)

(<script>
import { gettext_provider } from "./gettext-provider.js";
import WidgetModalRows from "./WidgetModalRows.vue";
import { formatDateDayMonthYear } from "./time-formatters.js";

export default {
    name: "WidgetModalTimes",
    components: { WidgetModalRows },
    props: {
        timeData: Array,
        totalTime: String
    },
    data() {
        const data = this.timeData[0];

        return {
            artifact: data.artifact,
            project: data.project,
            times: this.timeData
        };
    },
    computed: {
        date_message: () => gettext_provider.gettext("Date"),
        artifact_choice: () => gettext_provider.gettext("Artifact"),
        close_label: () => gettext_provider.gettext("Close"),
        label_project: () => gettext_provider.gettext("Project"),
        submission_label: () => gettext_provider.gettext("Submission date"),
        edit_time: () => gettext_provider.gettext("Detailed times"),
        steps_label: () => gettext_provider.gettext("Steps"),
        time_label: () => gettext_provider.gettext("Times"),

        timetracking_url() {
            return this.artifact.html_url + "&view=timetracking";
        },
        submission_date() {
            return formatDateDayMonthYear(this.artifact.submission_date);
        },
        new_id() {
            return "timetracking-artifact-details-modal-" + this.artifact.id;
        },
        sorted_times() {
            return this.times.sort((a, b) => {
                return new Date(b.date) - new Date(a.date);
            });
        },
        badge_color() {
            return "tlp-badge-" + this.artifact.badge_color;
        }
    }
};
</script>)
