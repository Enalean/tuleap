(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = [
        '$scope',
        '$filter',
        '$window',
        '$q',
        'gettextCatalog',
        'SharedPropertiesService',
        'BacklogItemService',
        'BacklogItemFactory',
        'MilestoneService',
        'ProjectService',
        'DroppedService',
        'CardFieldsService',
        'TuleapArtifactModalService',
        'NewTuleapArtifactModalService',
        'TuleapArtifactModalLoading',
        'UserPreferencesService'
    ];

    function PlanningCtrl(
        $scope,
        $filter,
        $window,
        $q,
        gettextCatalog,
        SharedPropertiesService,
        BacklogItemService,
        BacklogItemFactory,
        MilestoneService,
        ProjectService,
        DroppedService,
        CardFieldsService,
        TuleapArtifactModalService,
        NewTuleapArtifactModalService,
        TuleapArtifactModalLoading,
        UserPreferencesService
    ) {
        var self = this;

        _.extend(self, {
            init                            : init,
            loadBacklog                     : loadBacklog,
            loadInitialBacklogItems         : loadInitialBacklogItems,
            loadInitialMilestones           : loadInitialMilestones,
            isMilestoneContext              : isMilestoneContext,
            canBeAddedToBacklogItemChildren : canBeAddedToBacklogItemChildren,
            BACKLOG_ITEMS_PAGINATION        : { limit: 50, offset: 0 },
            BACKLOG_ITEM_CHILDREN_PAGINATION: { limit: 50, offset: 0 },
            OPEN_MILESTONES_PAGINATION      : { limit: 50, offset: 0 },
            CLOSED_MILESTONES_PAGINATION    : { limit: 50, offset: 0 },
            MILESTONE_CONTENT_PAGINATION    : { limit: 50, offset: 0 }
        });

        _.extend($scope, {
            backlog: {
                user_can_move_cards: false
            },
            backlog_items: {
                content         : [],
                filtered_content: [],
                fully_loaded    : false,
                loading         : false
            },
            compact_view_key     : 'compact-view',
            detailed_view_key    : 'detailed-view',
            show_closed_view_key : 'show-closed-view',
            hide_closed_view_key : 'hide-closed-view',
            current_milestone    : {},
            filter_terms         : '',
            items                : {},
            loading_modal        : TuleapArtifactModalLoading.loading,
            milestones           : {
                content                       : [],
                loading                       : false,
                open_milestones_fully_loaded  : false,
                closed_milestones_fully_loaded: false
            },
            rest_error           : '',
            rest_error_occured   : false,
            submilestone_type    : null,
            use_angular_new_modal: true,

            canBeAddedToBacklogItemChildren       : canBeAddedToBacklogItemChildren,
            appendBacklogItems                    : appendBacklogItems,
            canShowBacklogItem                    : canShowBacklogItem,
            displayBacklogItems                   : displayBacklogItems,
            displayUserCantPrioritizeForBacklog   : displayUserCantPrioritizeForBacklog,
            displayUserCantPrioritizeForMilestones: displayUserCantPrioritizeForMilestones,
            fetchAllBacklogItems                  : fetchAllBacklogItems,
            fetchBacklogItemChildren              : fetchBacklogItemChildren,
            fetchBacklogItems                     : fetchBacklogItems,
            filterBacklog                         : filterBacklog,
            generateMilestoneLinkUrl              : generateMilestoneLinkUrl,
            getInitialEffortMessage               : getInitialEffortMessage,
            refreshBacklogItem                    : refreshBacklogItem,
            refreshSubmilestone                   : refreshSubmilestone,
            showAddChildModal                     : showAddChildModal,
            showAddItemToSubMilestoneModal        : showAddItemToSubMilestoneModal,
            showAddSubmilestoneModal              : showAddSubmilestoneModal,
            showEditSubmilestoneModal             : showEditSubmilestoneModal,
            showChildren                          : showChildren,
            showCreateNewModal                    : showCreateNewModal,
            showEditModal                         : showEditModal,
            switchViewMode                        : switchViewMode,
            toggle                                : toggle,
            switchClosedMilestoneItemsViewMode    : switchClosedMilestoneItemsViewMode,
            getOpenMilestones                     : getOpenMilestones,
            getClosedMilestones                   : getClosedMilestones,
            displayClosedMilestones               : displayClosedMilestones,
            thereIsOpenMilestonesLoaded           : thereIsOpenMilestonesLoaded,
            thereIsClosedMilestonesLoaded         : thereIsClosedMilestonesLoaded,
            cardFieldIsCross                      : CardFieldsService.cardFieldIsCross,
            cardFieldIsDate                       : CardFieldsService.cardFieldIsDate,
            cardFieldIsFile                       : CardFieldsService.cardFieldIsFile,
            cardFieldIsList                       : CardFieldsService.cardFieldIsList,
            cardFieldIsPermissions                : CardFieldsService.cardFieldIsPermissions,
            cardFieldIsSimpleValue                : CardFieldsService.cardFieldIsSimpleValue,
            cardFieldIsText                       : CardFieldsService.cardFieldIsText,
            cardFieldIsUser                       : CardFieldsService.cardFieldIsUser,
            getCardFieldCrossValue                : CardFieldsService.getCardFieldCrossValue,
            getCardFieldFileValue                 : CardFieldsService.getCardFieldFileValue,
            getCardFieldListValues                : CardFieldsService.getCardFieldListValues,
            getCardFieldPermissionsValue          : CardFieldsService.getCardFieldPermissionsValue,
            getCardFieldTextValue                 : CardFieldsService.getCardFieldTextValue,
            getCardFieldUserValue                 : CardFieldsService.getCardFieldUserValue
        });

        $scope.treeOptions = {
            accept : isItemDroppable,
            dropped: dropped
        };

        self.init();

        function init() {
            self.user_id                   = SharedPropertiesService.getUserId();
            self.project_id                = SharedPropertiesService.getProjectId();
            self.milestone_id              = parseInt(SharedPropertiesService.getMilestoneId(), 10);
            self.use_angular_new_modal     = SharedPropertiesService.getUseAngularNewModal();
            $scope.use_angular_new_modal   = self.use_angular_new_modal;

            initViewModes(SharedPropertiesService.getViewMode());
            self.loadBacklog(SharedPropertiesService.getMilestone());
            self.loadInitialBacklogItems(SharedPropertiesService.getInitialBacklogItems());
            self.loadInitialMilestones(SharedPropertiesService.getInitialMilestones());
        }

        function initViewModes(view_mode) {
            $scope.current_view_class        = $scope.compact_view_key;
            $scope.current_closed_view_class = $scope.show_closed_view_key;

            if (view_mode) {
                $scope.current_view_class = view_mode;
            }
        }

        function switchViewMode(view_mode) {
            $scope.current_view_class = view_mode;
            UserPreferencesService.setPreference(self.user_id, 'agiledashboard_planning_item_view_mode_' + self.project_id, view_mode);
        }

        function switchClosedMilestoneItemsViewMode(view_mode) {
            $scope.current_closed_view_class = view_mode;
        }


        function isMilestoneContext() {
            return ! isNaN(self.milestone_id);
        }

        function loadBacklog(initial_milestone) {
            if (! self.isMilestoneContext()) {
                loadProject();

            } else {
                if (initial_milestone) {
                    MilestoneService.defineAllowedBacklogItemTypes(initial_milestone);
                    MilestoneService.augmentMilestone(initial_milestone, self.MILESTONE_CONTENT_PAGINATION.limit, self.MILESTONE_CONTENT_PAGINATION.offset, $scope.items);

                    loadMilestone(initial_milestone);

                } else {
                    MilestoneService.getMilestone(self.milestone_id, self.MILESTONE_CONTENT_PAGINATION.limit, self.MILESTONE_CONTENT_PAGINATION.offset, $scope.items).then(function(data) {
                        loadMilestone(data.results);
                    });
                }
            }

            function loadProject() {
                $scope.backlog = {
                    rest_base_route: 'projects',
                    rest_route_id  : self.project_id,
                    accepted_types : {
                        toString: function() {
                            return '';
                        }
                    }
                };

                fetchProjectBacklogAcceptedTypes(self.project_id);
                fetchProjectSubmilestoneType(self.project_id);
            }

            function loadMilestone(milestone) {
                $scope.current_milestone = milestone;
                $scope.submilestone_type = milestone.sub_milestone_type;
                $scope.backlog           = {
                    rest_base_route    : 'milestones',
                    rest_route_id      : self.milestone_id,
                    accepted_types     : milestone.backlog_accepted_types,
                    user_can_move_cards: milestone.has_user_priority_change_permission
                };
            }

            function fetchProjectSubmilestoneType(project_id) {
                return ProjectService.getProject(project_id).then(function(response) {
                    $scope.submilestone_type = response.data.additional_informations.agiledashboard.root_planning.milestone_tracker;
                });
            }

            function fetchProjectBacklogAcceptedTypes(project_id) {
                return ProjectService.getProjectBacklog(project_id).then(function(data) {
                    $scope.backlog.accepted_types      = data.allowed_backlog_item_types;
                    $scope.backlog.user_can_move_cards = data.has_user_priority_change_permission;
                });
            }
        }


        function loadInitialBacklogItems(initial_backlog_items) {
            if (initial_backlog_items) {
                _.forEach(initial_backlog_items.backlog_items_representations, function(backlog_item) {
                    BacklogItemFactory.augment(backlog_item);
                });

                $scope.appendBacklogItems(initial_backlog_items.backlog_items_representations);

                self.BACKLOG_ITEMS_PAGINATION.offset = self.BACKLOG_ITEMS_PAGINATION.limit;
                $scope.backlog_items.fully_loaded = self.BACKLOG_ITEMS_PAGINATION.offset >= initial_backlog_items.total_size;

            } else {
                displayBacklogItems();
            }
        }

        function displayBacklogItems() {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.when();
            }

            return $scope.fetchBacklogItems(self.BACKLOG_ITEMS_PAGINATION.limit, self.BACKLOG_ITEMS_PAGINATION.offset).then(function(total) {
                self.BACKLOG_ITEMS_PAGINATION.offset += self.BACKLOG_ITEMS_PAGINATION.limit;
                $scope.backlog_items.fully_loaded     = self.BACKLOG_ITEMS_PAGINATION.offset >= total;
            });
        }

        function backlogItemsAreLoadingOrAllLoaded() {
            return ($scope.backlog_items.loading || $scope.backlog_items.fully_loaded);
        }

        function fetchBacklogItems(limit, offset) {
            $scope.backlog_items.loading = true;
            var promise;

            if (self.isMilestoneContext()) {
                promise = BacklogItemService.getMilestoneBacklogItems(self.milestone_id, limit, offset);
            } else {
                promise = BacklogItemService.getProjectBacklogItems(self.project_id, limit, offset);
            }

            return promise.then(function(data) {
                var items = data.results;
                $scope.appendBacklogItems(items);

                return data.total;
            });
        }

        function appendBacklogItems(items) {
            _.extend($scope.items, _.indexBy(items, 'id'));
            var backlog_items     = $scope.backlog_items;
            backlog_items.content = backlog_items.content.concat(items);
            backlog_items.loading = false;
            applyFilter();
        }

        function fetchAllBacklogItems(limit, offset) {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.reject();
            }

            return $scope.fetchBacklogItems(limit, offset).then(function(total) {
                if ((offset + limit) > total) {
                    $scope.backlog_items.fully_loaded = true;
                    return;
                } else {
                    return fetchAllBacklogItems(limit, offset + limit);
                }
            });
        }

        function filterBacklog() {
            $scope.fetchAllBacklogItems(self.BACKLOG_ITEMS_PAGINATION.limit, self.BACKLOG_ITEMS_PAGINATION.offset)
                ['finally'](function() {
                    applyFilter();
                });
        }

        function applyFilter() {
            $scope.backlog_items.filtered_content = $filter('InPropertiesFilter')($scope.backlog_items.content, $scope.filter_terms);
        }


        function loadInitialMilestones(initial_milestones) {
            if (initial_milestones) {
                _.forEach(initial_milestones.milestones_representations, function(milestone) {
                    MilestoneService.augmentMilestone(milestone, self.MILESTONE_CONTENT_PAGINATION.limit, self.MILESTONE_CONTENT_PAGINATION.offset, $scope.items);
                });

                $scope.milestones.content              = initial_milestones.milestones_representations;
                self.OPEN_MILESTONES_PAGINATION.offset = self.OPEN_MILESTONES_PAGINATION.limit;

                if (self.OPEN_MILESTONES_PAGINATION.offset < initial_milestones.total_size) {
                    displayOpenMilestones();
                } else {
                    $scope.milestones.loading = false;
                }

            } else {
                displayOpenMilestones();
            }
        }

        function displayOpenMilestones() {
            if (! self.isMilestoneContext()) {
                fetchOpenMilestones(self.project_id, self.OPEN_MILESTONES_PAGINATION.limit, self.OPEN_MILESTONES_PAGINATION.offset);
            } else {
                fetchOpenSubMilestones(self.milestone_id, self.OPEN_MILESTONES_PAGINATION.limit, self.OPEN_MILESTONES_PAGINATION.offset);
            }
        }

        function displayClosedMilestones() {
            $scope.milestones.loading = true;

            if (! self.isMilestoneContext()) {
                fetchClosedMilestones(self.project_id, self.CLOSED_MILESTONES_PAGINATION.limit, self.CLOSED_MILESTONES_PAGINATION.offset);
            } else {
                fetchClosedSubMilestones(self.milestone_id, self.CLOSED_MILESTONES_PAGINATION.limit, self.CLOSED_MILESTONES_PAGINATION.offset);
            }
        }

        function fetchOpenMilestones(project_id, limit, offset) {
            $scope.milestones.loading = true;

            return MilestoneService.getOpenMilestones(project_id, limit, offset, $scope.items).then(function(data) {
                var milestones            = [].concat($scope.milestones.content).concat(data.results);
                $scope.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchOpenMilestones(project_id, limit, offset + limit);
                } else {
                    $scope.milestones.loading                      = false;
                    $scope.milestones.open_milestones_fully_loaded = true;
                }
            });
        }

        function fetchOpenSubMilestones(milestone_id, limit, offset) {
            $scope.milestones.loading = true;

            return MilestoneService.getOpenSubMilestones(milestone_id, limit, offset, $scope.items).then(function(data) {
                var milestones            = [].concat($scope.milestones.content).concat(data.results);
                $scope.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchOpenSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    $scope.milestones.loading                      = false;
                    $scope.milestones.open_milestones_fully_loaded = true;
                }
            });
        }

        function fetchClosedMilestones(project_id, limit, offset) {
            $scope.milestones.loading = true;

            return MilestoneService.getClosedMilestones(project_id, limit, offset, $scope.items).then(function(data) {
                var milestones            = [].concat($scope.milestones.content).concat(data.results);
                $scope.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchClosedMilestones(project_id, limit, offset + limit);
                } else {
                    $scope.milestones.loading                        = false;
                    $scope.milestones.closed_milestones_fully_loaded = true;
                }
            });
        }

        function fetchClosedSubMilestones(milestone_id, limit, offset) {
            $scope.milestones.loading = true;

            return MilestoneService.getClosedSubMilestones(milestone_id, limit, offset, $scope.items).then(function(data) {
                var milestones            = [].concat($scope.milestones.content).concat(data.results);
                $scope.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchClosedSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    $scope.milestones.loading                        = false;
                    $scope.milestones.closed_milestones_fully_loaded = true;
                }
            });
        }

        function getOpenMilestones() {
            return $filter('filter')($scope.milestones.content, {semantic_status: 'open'});
        }

        function getClosedMilestones() {
            return $filter('filter')($scope.milestones.content, {semantic_status: 'closed'});
        }

        function thereIsOpenMilestonesLoaded() {
            var open_milestones = getOpenMilestones();

            return open_milestones.length > 0;
        }

        function thereIsClosedMilestonesLoaded() {
            var closed_milestones = getClosedMilestones();

            return closed_milestones.length > 0;
        }

        function generateMilestoneLinkUrl(milestone, pane) {
            return '?group_id=' + self.project_id + '&planning_id=' + milestone.planning.id + '&action=show&aid=' + milestone.id + '&pane=' + pane;
        }


        function showCreateNewModal($event, item_type, backlog) {
            $event.preventDefault();

            var compared_to;
            if (! _.isEmpty($scope.backlog_items.content)) {
                compared_to = {
                    direction : "before",
                    item_id   : $scope.backlog_items.content[0].id
                };
            }

            var callback = function(item_id) {
                var promise;
                if (! self.isMilestoneContext()) {
                    if (compared_to) {
                        promise = ProjectService.removeAddReorderToBacklog(undefined, backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = ProjectService.removeAddToBacklog(undefined, backlog.rest_route_id, item_id);
                    }
                } else {
                    if (compared_to) {
                        promise = MilestoneService.removeAddReorderToBacklog(undefined, backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = MilestoneService.removeAddToBacklog(undefined, backlog.rest_route_id, item_id);
                    }
                }

                promise.then(function() {
                    var subpromise;
                    if ($scope.filter_terms) {
                        subpromise = prependItemToFilteredBacklog(item_id);
                    } else {
                        subpromise = prependItemToBacklog(item_id);
                    }
                    return subpromise;
                });

                return promise;
            };

            if (self.use_angular_new_modal) {
                var parent_item = (! _.isEmpty($scope.current_milestone)) ? $scope.current_milestone : undefined;
                NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);

            } else {
                TuleapArtifactModalService.showCreateItemForm(item_type.id, backlog.rest_route_id, callback);
            }
        }

        function showAddChildModal($event, item_type, parent_item) {
            $event.preventDefault();

            var callback = function(item_id) {
                var promise = BacklogItemService.removeAddBacklogItemChildren(undefined, parent_item.id, item_id);

                promise.then(function() {
                    return appendItemToBacklogItem(item_id, parent_item);
                });

                return promise;
            };

            NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
        }

        function showEditSubmilestoneModal($event, submilestone) {
            var when_left_mouse_click = 1;

            if($event.which === when_left_mouse_click) {
                $event.preventDefault();

                NewTuleapArtifactModalService.showEdition(
                    self.user_id,
                    submilestone.artifact.tracker.id,
                    submilestone.artifact.id,
                    $scope.refreshSubmilestone
                );
            }
        }

        function showAddSubmilestoneModal($event, submilestone_type) {
            $event.preventDefault();

            var callback = function(submilestone_id) {
                if (! self.isMilestoneContext()) {
                    return prependSubmilestoneToSubmilestoneList(submilestone_id);

                } else {
                    var submilestone_ids = [];
                    _.forEach($scope.milestones.content, function(milestone) {
                        submilestone_ids.push(milestone.id);
                    });

                    submilestone_ids.push(submilestone_id);

                    var promise = MilestoneService.putSubMilestones($scope.backlog.rest_route_id, submilestone_ids);
                    promise.then(function() {
                        return prependSubmilestoneToSubmilestoneList(submilestone_id);
                    });

                    return promise;
                }
            };

            var parent_item = (! _.isEmpty($scope.current_milestone)) ? $scope.current_milestone : undefined;
            NewTuleapArtifactModalService.showCreation(submilestone_type.id, parent_item, callback);
        }

        function prependSubmilestoneToSubmilestoneList(submilestone_id) {
            return MilestoneService.getMilestone(submilestone_id, self.MILESTONE_CONTENT_PAGINATION.limit, self.MILESTONE_CONTENT_PAGINATION.offset, $scope.items).then(function(data) {
                $scope.milestones.content.unshift(data.results);
            });
        }

        function showAddItemToSubMilestoneModal(item_type, parent_item) {
            var compared_to;
            if (!_.isEmpty(parent_item.content)) {
                compared_to = {
                    direction: "before",
                    item_id  : parent_item.content[0].id
                };
            }

            var callback = function(item_id) {
                var promise;
                if (compared_to) {
                    promise = MilestoneService.addReorderToContent(parent_item.id, item_id, compared_to);
                } else {
                    promise = MilestoneService.addToContent(parent_item.id, item_id);
                }

                promise.then(function() {
                    return prependItemToSubmilestone(item_id, parent_item);
                });

                return promise;
            };

            NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
        }

        function showEditModal($event, backlog_item, milestone) {
            var when_left_mouse_click = 1;

            var callback = function(item_id) {
                return $scope.refreshBacklogItem(item_id).then(function() {
                    if (milestone) {
                        MilestoneService.updateInitialEffort(milestone);
                    }
                });
            };

            if($event.which === when_left_mouse_click) {
                $event.preventDefault();

                NewTuleapArtifactModalService.showEdition(
                    self.user_id,
                    backlog_item.artifact.tracker.id,
                    backlog_item.artifact.id,
                    callback
                );
            }
        }

        function appendItemToBacklogItem(child_item_id, parent_item) {
            return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
                $scope.items[child_item_id] = data.backlog_item;
                refreshBacklogItem(parent_item.id);

                if (canBeAddedToBacklogItemChildren(child_item_id, parent_item)) {
                    parent_item.children.data.push($scope.items[child_item_id]);
                }
            });
        }

        function canBeAddedToBacklogItemChildren(child_item_id, parent_item) {
            if (! parent_item.has_children) {
                return true;
            }

            if (parent_item.has_children && parent_item.children.loaded) {
                var child_already_in_children = _.find(parent_item.children.data, { id: child_item_id });

                if (child_already_in_children === undefined) {
                    return true;
                }
            }

            return false;
        }

        function prependItemToSubmilestone(child_item_id, parent_item) {
            return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
                $scope.items[child_item_id] = data.backlog_item;

                parent_item.content.unshift(data.backlog_item);
                MilestoneService.updateInitialEffort(parent_item);
            });
        }

        function prependItemToBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                $scope.items[backlog_item_id] = new_item;
                $scope.backlog_items.content.unshift(new_item);
                $scope.backlog_items.filtered_content = $scope.backlog_items.content;
            });
        }

        function prependItemToFilteredBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                $scope.items[backlog_item_id] = new_item;
                $scope.backlog_items.content.unshift(new_item);
            });
        }

        function refreshSubmilestone(submilestone_id) {
            var submilestone = _.find($scope.milestones.content, { 'id': submilestone_id });

            submilestone.updating = true;

            return MilestoneService.getMilestone(submilestone_id).then(function(data) {
                submilestone.label           = data.results.label;
                submilestone.capacity        = data.results.capacity;
                submilestone.semantic_status = data.results.semantic_status;
                submilestone.status_value    = data.results.status_value;
                submilestone.start_date      = data.results.start_date;
                submilestone.end_date        = data.results.end_date;
                submilestone.updating        = false;
            });
        }

        function refreshBacklogItem(backlog_item_id) {
            $scope.items[backlog_item_id].updating = true;

            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                $scope.items[backlog_item_id].label          = data.backlog_item.label;
                $scope.items[backlog_item_id].initial_effort = data.backlog_item.initial_effort;
                $scope.items[backlog_item_id].card_fields    = data.backlog_item.card_fields;
                $scope.items[backlog_item_id].updating       = false;
                $scope.items[backlog_item_id].status         = data.backlog_item.status;
            });
        }

        function toggle($event, milestone) {
            if (! milestone.alreadyLoaded && milestone.content.length === 0) {
                milestone.getContent();
            }

            var target                = $event.target;
            var is_a_create_item_link = false;

            if (target.classList) {
                is_a_create_item_link = target.classList.contains('create-item-link');
            } else {
                is_a_create_item_link = target.parentNode.getElementsByClassName("create-item-link")[0] !== undefined;
            }

            if (! is_a_create_item_link) {
                return milestone.collapsed = ! milestone.collapsed;
            }
        }

        function showChildren(scope, backlog_item) {
            scope.toggle();

            if (backlog_item.has_children && ! backlog_item.children.loaded) {
                backlog_item.loading = true;
                fetchBacklogItemChildren(backlog_item, self.BACKLOG_ITEM_CHILDREN_PAGINATION.limit, self.BACKLOG_ITEM_CHILDREN_PAGINATION.offset);
            }
        }

        function fetchBacklogItemChildren(backlog_item, limit, offset) {
            return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(function(data) {
                angular.forEach(data.results, function(child) {
                    $scope.items[child.id] = child;
                    backlog_item.children.data.push(child);
                });

                if ((offset + limit) < data.total) {
                    fetchBacklogItemChildren(backlog_item, limit, offset + limit);
                } else {
                    backlog_item.loading         = false;
                    backlog_item.children.loaded = true;
                }
            });
        }

        function canShowBacklogItem(backlog_item) {
            if (typeof backlog_item.isOpen === 'function') {
                return backlog_item.isOpen() || $scope.current_closed_view_class === $scope.show_closed_view_key;
            }

            return true;
        }

        function isItemDroppable(sourceNodeScope, destNodesScope, destIndex) {
            if (typeof destNodesScope.$element.attr === 'undefined' || destNodesScope.$element.attr('data-nodrag') === 'true') {
                return;
            }

            var accepted     = destNodesScope.$element.attr('data-accept').split('|');
            var type         = sourceNodeScope.$element.attr('data-type');
            var is_droppable = false;

            for (var i = 0; i < accepted.length; i++) {
                if (accepted[i] === type) {
                    is_droppable = true;
                    continue;
                }
            }

            return is_droppable;
        }

        function hideUserCantPrioritizeForBacklog() {
            return $scope.backlog.user_can_move_cards || $scope.backlog_items.content.length === 0;
        }

        function displayUserCantPrioritizeForBacklog() {
            return ! hideUserCantPrioritizeForBacklog();
        }

        function hideUserCantPrioritizeForMilestones() {
            if ($scope.milestones.content.length === 0) {
                return true;
            }

            return $scope.milestones.content[0].has_user_priority_change_permission;
        }

        function displayUserCantPrioritizeForMilestones() {
            return ! hideUserCantPrioritizeForMilestones();
        }

        function getInitialEffortMessage(initial_effort) {
            return gettextCatalog.getPlural(initial_effort, "pt", "pts");
        }

        function dropped(event) {
            var source_list_element = event.source.nodesScope.$element,
                dest_list_element   = event.dest.nodesScope.$element,
                dropped_item_id     = event.source.nodeScope.$modelValue.id,
                compared_to         = DroppedService.defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index);

            saveChange();
            updateSubmilestonesInitialEffort();
            collapseSourceParentIfNeeded();
            removeFromDestinationIfNeeded();

            function saveChange() {
                switch(true) {
                    case movedInTheSameList():
                        if (source_list_element.hasClass('backlog')) {
                            DroppedService
                                .reorderBacklog(dropped_item_id, compared_to, $scope.backlog)
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('submilestone')) {
                            DroppedService
                                .reorderSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('backlog-item-children')) {
                            DroppedService
                                .reorderBacklogItemChildren(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-backlog-item-id'), 10))
                                .then(function() {}, catchError);
                        }
                        break;

                    case movedFromBacklogToSubmilestone():
                        DroppedService
                            .moveFromBacklogToSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                            .then(function() {
                                _.remove($scope.backlog_items.content, function(item) {
                                    return item.id === dropped_item_id;
                                });
                            }, catchError);
                        break;

                    case movedFromChildrenToChildren():
                        var backlog_item_id_source = parseInt(source_list_element.attr('data-backlog-item-id'), 10),
                            backlog_item_id_dest   = parseInt(dest_list_element.attr('data-backlog-item-id'), 10);

                        $scope.items[backlog_item_id_source].updating = true;
                        $scope.items[backlog_item_id_dest].updating   = true;

                        DroppedService
                            .moveFromChildrenToChildren(
                                dropped_item_id,
                                compared_to,
                                backlog_item_id_source,
                                backlog_item_id_dest
                            )
                            .then(function() {
                                refreshBacklogItem(backlog_item_id_source);
                                refreshBacklogItem(backlog_item_id_dest);

                            }, catchError);
                        break;

                    case movedFromSubmilestoneToBacklog():
                        DroppedService
                            .moveFromSubmilestoneToBacklog(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                $scope.backlog
                            )
                            .then(function() {}, catchError);
                        break;

                    case movedFromOneSubmilestoneToAnother():
                        DroppedService
                            .moveFromSubmilestoneToSubmilestone(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                parseInt(dest_list_element.attr('data-submilestone-id'), 10)
                            )
                            .then(function() {}, catchError);
                        break;
                }

                function catchError(data) {
                    $scope.rest_error_occured = true;
                    $scope.rest_error         = data.data.error.code + ' ' + data.data.error.message;
                }

                function movedInTheSameList() {
                    return event.source.nodesScope.$id === event.dest.nodesScope.$id;
                }

                function movedFromBacklogToSubmilestone() {
                    return source_list_element.hasClass('backlog') && dest_list_element.hasClass('submilestone');
                }

                function movedFromChildrenToChildren() {
                    return source_list_element.hasClass('backlog-item-children') && dest_list_element.hasClass('backlog-item-children');
                }

                function movedFromSubmilestoneToBacklog() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('backlog');
                }

                function movedFromOneSubmilestoneToAnother() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('submilestone');
                }
            }

            function updateSubmilestonesInitialEffort() {
                if (source_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones.content, function(milestone) {
                        return milestone.id == source_list_element.attr('data-submilestone-id');
                    }));
                }

                if (dest_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones.content, function(milestone) {
                        return milestone.id == dest_list_element.attr('data-submilestone-id');
                    }));
                }
            }

            function collapseSourceParentIfNeeded() {
                if (event.sourceParent && ! event.sourceParent.hasChild()) {
                    event.sourceParent.collapse();
                }
            }

            function removeFromDestinationIfNeeded() {
                if (event.dest.nodesScope.collapsed &&
                    event.dest.nodesScope.$nodeScope.$modelValue.has_children &&
                    ! event.dest.nodesScope.$nodeScope.$modelValue.children.loaded) {

                    event.dest.nodesScope.childNodes()[0].remove();
                }
            }
        }
    }
})();
