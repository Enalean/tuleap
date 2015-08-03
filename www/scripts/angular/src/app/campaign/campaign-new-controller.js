angular
    .module('campaign')
    .controller('CampaignNewCtrl', CampaignNewCtrl);

CampaignNewCtrl.$inject = [
    '$scope',
    '$state',
    '$filter',
    'gettextCatalog',
    'CampaignService',
    'EnvironmentService',
    'DefinitionService',
    'SharedPropertiesService'
];

function CampaignNewCtrl(
    $scope,
    $state,
    $filter,
    gettextCatalog,
    CampaignService,
    EnvironmentService,
    DefinitionService,
    SharedPropertiesService
) {
    var project_id              = SharedPropertiesService.getProjectId(),
        controller_is_destroyed = false;

    _.extend($scope, {
        ITEMS_PER_PAGE:         15,
        nb_total_definitions:   0,
        loading_definitions:    true,
        loading_environments:   true,
        definitions:            [],
        submitting_campaign:    false,
        select_all:             false,
        breadcrumb_label:       gettextCatalog.getString('Campaign creation'),
        getFilteredDefinitions: getFilteredDefinitions,
        createCampaign:         createCampaign,
        selectAll:              selectAll,
        selectAllTotal:         selectAllTotal,
        campaign: {
            label:        '',
            environments: []
        },
        selectADefinitionForEnvironment: selectADefinitionForEnvironment
    });

    getEnvironments(project_id, 50, 0);
    getDefinitions(project_id, 750, 0);

    $scope.$on('$destroy', function iVeBeenDismissed() {
        controller_is_destroyed = true;
    });

    function createCampaign(campaign) {
        var environments = extractChoosenDefinitionsByEnvironment(campaign);

        $scope.submitting_campaign = true;

        CampaignService
            .createCampaign({
              project_id:   project_id,
              label:        campaign.label,
              environments: environments
            })
            .then(function () {
                $state.go('campaigns.list', {}, {reload: true});
        });
    }

    function extractChoosenDefinitionsByEnvironment(campaign) {
        var environments = {};

        campaign.environments.forEach(function (environment) {
            var definition_ids = _(environment.definitions)
                .omit(shouldValueBeOmitted)
                .keys()
                .value();

            if (definition_ids.length > 0) {
                environments[environment.id] = definition_ids;
            }
        });

        return environments;
    }

    function shouldValueBeOmitted(value) {
        return ! value;
    }

    function getEnvironments(project_id, limit, offset) {
        EnvironmentService.getEnvironments(project_id, limit, offset).then(function(data) {
            data.results.forEach(addPossibleEnvironmentInCampaign);

            if ($scope.campaign.environments.length < data.total) {
                getEnvironments(project_id, limit, offset + limit);
            } else {
                $scope.loading_environments = false;
            }
        });
    }

    function addPossibleEnvironmentInCampaign(environment) {
        $scope.campaign.environments.push({
            label:           environment.label,
            id:              environment.id,
            is_choosen:      false,
            current_page:    1,
            select_all:      false,
            nb_selected_all: 0,
            filter:          '',
            definitions:     {}
        });
    }

    function getDefinitions(project_id, limit, offset) {
        DefinitionService.getDefinitions(project_id, limit, offset).then(function(data) {
            $scope.definitions = $scope.definitions.concat(data.results);
            $scope.nb_total_definitions = data.total;

            if (! controller_is_destroyed && $scope.definitions.length < $scope.nb_total_definitions) {
                getDefinitions(project_id, limit, offset + limit);
            } else {
                $scope.loading_definitions = false;
            }
        });
    }

    function getFilteredDefinitions(filter) {
        return $filter('InPropertiesFilter')(
            $scope.definitions,
            filter,
            ['id','summary','category']
        );
    }

    function selectAll(environment, items_per_page) {
        if (! environment.select_all) {
            unSelectAll(environment);
            return;
        }

        var filtered_definitions        = getFilteredDefinitions(environment.filter),
            definitions_on_current_page = filtered_definitions.slice(
                (environment.current_page - 1) * items_per_page,
                environment.current_page * items_per_page
            );

        definitions_on_current_page.forEach(function (definition) {
            addDefinitionToEnvironment(environment, definition);
        });

        environment.page_selected_all     = environment.current_page;
        environment.nb_selected_all       = definitions_on_current_page.length;
        environment.nb_selected_all_total = filtered_definitions.length;
    }

    function unSelectAll(environment) {
        getFilteredDefinitions(environment.filter).forEach(function (definition) {
            removeDefinitionFromEnvironment(environment, definition);
        });
    }

    function selectAllTotal(environment) {
        var filtered_definitions = getFilteredDefinitions(environment.filter);

        filtered_definitions.forEach(function (definition) {
            addDefinitionToEnvironment(environment, definition);
        });

        environment.nb_selected_all       = filtered_definitions.length;
        environment.nb_selected_all_total = filtered_definitions.length;
    }

    function addDefinitionToEnvironment(environment, definition) {
        environment.definitions[definition.id] = true;
    }

    function removeDefinitionFromEnvironment(environment, definition) {
        environment.definitions[definition.id] = false;
    }

    function selectADefinitionForEnvironment(environment) {
        environment.select_all = false;
    }
}