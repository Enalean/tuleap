import "./campaign-new.tpl.html";

import { getCampaigns } from "../api/rest-querier.js";
import { setError } from "../feedback-state.js";
import { KeyboardShortcuts } from "../keyboard-navigation/setup-shortcuts";

export default CampaignListCtrl;

CampaignListCtrl.$inject = [
    "$q",
    "gettextCatalog",
    "TlpModalService",
    "SharedPropertiesService",
    "milestone",
];

function CampaignListCtrl($q, gettextCatalog, TlpModalService, SharedPropertiesService, milestone) {
    const self = this;
    const project_id = SharedPropertiesService.getProjectId();

    Object.assign(self, {
        campaigns: [],
        filtered_campaigns: [],
        closed_campaigns_hidden: true,
        closed_campaigns_loaded: false,
        has_open_campaigns: false,
        loading: true,
        open_campaigns: [],
        open_campaigns_loaded: false,
        $onInit: init,
        hideClosedCampaigns,
        loadClosedCampaigns,
        shouldShowHideClosedButton,
        shouldShowLoadClosedButton,
        shouldShowNoCampaigns,
        shouldShowNoOpenCampaigns,
        showClosedCampaigns,
        openNewCampaignModal,
    });

    function init() {
        const keyboard_shortcuts = new KeyboardShortcuts(gettextCatalog);
        keyboard_shortcuts.setCampaignsListPageShortcuts();

        return loadCampaigns(project_id);
    }

    function loadCampaigns(project_id) {
        self.loading = true;

        return $q
            .when(getCampaigns(project_id, milestone.id, "open"))
            .then((campaigns) => {
                self.open_campaigns = campaigns;
                self.campaigns = campaigns;
                self.filtered_campaigns = campaigns;
                self.has_open_campaigns = campaigns.length > 0;
                self.open_campaigns_loaded = true;
            }, handleRESTErrors)
            .finally(() => {
                self.loading = false;
            });
    }

    function handleRESTErrors(e) {
        if (!e.response) {
            throw e;
        }

        return e.response.json().then(({ error }) => {
            const message = error.message;
            setError(
                gettextCatalog.getString(
                    "An error occurred while loading the campaigns. Please refresh the page. {{ message }}",
                    { message },
                ),
            );
        });
    }

    function loadClosedCampaigns() {
        self.loading = true;

        return $q
            .when(getCampaigns(project_id, milestone.id, "closed"))
            .then((campaigns) => {
                self.campaigns = self.campaigns.concat(campaigns);
                self.closed_campaigns_loaded = true;
            }, handleRESTErrors)
            .finally(() => {
                self.loading = false;
            });
    }

    function shouldShowNoCampaigns() {
        return (
            self.open_campaigns_loaded &&
            self.closed_campaigns_loaded &&
            self.campaigns.length === 0
        );
    }

    function shouldShowNoOpenCampaigns() {
        return (
            self.open_campaigns_loaded && !self.has_open_campaigns && !self.shouldShowNoCampaigns()
        );
    }

    function shouldShowLoadClosedButton() {
        return (
            !self.shouldShowNoCampaigns() &&
            (!self.closed_campaigns_loaded || self.closed_campaigns_hidden)
        );
    }

    function shouldShowHideClosedButton() {
        return !self.closed_campaigns_hidden && !self.shouldShowNoCampaigns();
    }

    function showClosedCampaigns() {
        let promise;
        if (!self.closed_campaigns_loaded) {
            promise = self.loadClosedCampaigns();
        }

        $q.when(promise).then(() => {
            self.filtered_campaigns = self.campaigns;
            self.closed_campaigns_hidden = false;
        });
    }

    function hideClosedCampaigns() {
        self.filtered_campaigns = self.open_campaigns;
        self.closed_campaigns_hidden = true;
    }

    function openNewCampaignModal() {
        return TlpModalService.open({
            templateUrl: "campaign-new.tpl.html",
            controller: "CampaignNewCtrl",
            controllerAs: "campaign_modal",
        });
    }
}
