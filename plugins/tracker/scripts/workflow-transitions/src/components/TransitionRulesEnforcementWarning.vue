<template>
    <div class="tracker-workflow-rules-enforcement-warning">
        <div v-if="is_workflow_legacy === true" class="tlp-alert-warning">
            <p>
                {{
                    $gettext(
                        "This workflow has an incoherent behaviour: pre conditions and post actions defined in transitions are processed even if the workflow is not activated.",
                    )
                }}
            </p>
            <p>
                {{
                    $gettext(
                        'By clicking on "Fully deactivate the workflow", the workflow\'s behaviour will be fixed.',
                    )
                }}
            </p>
            <button class="tlp-button-warning" v-on:click="deactivateLegacyTransitions()">
                <span>{{ $gettext("Fully deactivate the workflow") }}</span>
                <i v-if="is_loading" class="tlp-button-icon fas fa-circle-notch fa-spin"></i>
            </button>
        </div>
        <div
            v-if="are_transition_rules_enforced === true"
            key="enforcement_enabled"
            class="tlp-alert-success"
            data-test-message="rules-enforcement-active"
        >
            {{ $gettext("Transition rules are currently applied.") }}
        </div>
        <div
            v-if="are_transition_rules_enforced === false"
            key="enforcement_disabled"
            class="tlp-alert-warning"
            data-test-message="rules-enforcement-inactive"
        >
            {{ $gettext("Transition rules don't apply yet.") }}
        </div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";

export default {
    name: "TransitionRulesEnforcementWarning",
    data() {
        return {
            is_loading: false,
        };
    },
    computed: {
        ...mapGetters(["are_transition_rules_enforced", "is_workflow_legacy"]),
    },
    methods: {
        async deactivateLegacyTransitions() {
            this.is_loading = true;

            await this.$store.dispatch("deactivateLegacyTransitions");

            this.is_loading = false;
        },
    },
};
</script>
