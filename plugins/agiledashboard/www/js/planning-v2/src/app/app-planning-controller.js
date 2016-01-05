(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = [
        '$filter',
        '$q',
        'gettextCatalog',
        'SharedPropertiesService',
        'BacklogService',
        'BacklogItemService',
        'BacklogItemFactory',
        'MilestoneService',
        'ProjectService',
        'DroppedService',
        'CardFieldsService',
        'TuleapArtifactModalService',
        'NewTuleapArtifactModalService',
        'TuleapArtifactModalLoading',
        'UserPreferencesService',
        'RestErrorService',
        'BacklogItemCollectionService',
        'MilestoneCollectionService'
    ];

    function PlanningCtrl(
        $filter,
        $q,
        gettextCatalog,
        SharedPropertiesService,
        BacklogService,
        BacklogItemService,
        BacklogItemFactory,
        MilestoneService,
        ProjectService,
        DroppedService,
        CardFieldsService,
        TuleapArtifactModalService,
        NewTuleapArtifactModalService,
        TuleapArtifactModalLoading,
        UserPreferencesService,
        RestErrorService,
        BacklogItemCollectionService,
        MilestoneCollectionService
    ) {
        var self = this;

        _.extend(self, {
            filter_terms : '',
            backlog      : BacklogService.backlog,
            backlog_items: BacklogService.items,
            items        : BacklogItemCollectionService.items,
            milestones   : MilestoneCollectionService.milestones,
            compact_view_key     : 'compact-view',
            detailed_view_key    : 'detailed-view',
            show_closed_view_key : 'show-closed-view',
            hide_closed_view_key : 'hide-closed-view',
            loading_modal        : TuleapArtifactModalLoading.loading,
            use_angular_new_modal: true,
            canShowBacklogItem                    : canShowBacklogItem,
            displayBacklogItems                   : displayBacklogItems,
            displayClosedMilestones               : displayClosedMilestones,
            displayUserCantPrioritizeForMilestones: displayUserCantPrioritizeForMilestones,
            fetchAllBacklogItems                  : fetchAllBacklogItems,
            fetchBacklogItems                     : fetchBacklogItems,
            filterBacklog                         : filterBacklog,
            generateMilestoneLinkUrl              : generateMilestoneLinkUrl,
            getClosedMilestones                   : getClosedMilestones,
            getInitialEffortMessage               : getInitialEffortMessage,
            getOpenMilestones                     : getOpenMilestones,
            init                                  : init,
            isMilestoneContext                    : isMilestoneContext,
            loadBacklog                           : loadBacklog,
            loadInitialBacklogItems               : loadInitialBacklogItems,
            loadInitialMilestones                 : loadInitialMilestones,
            refreshSubmilestone                   : refreshSubmilestone,
            showAddBacklogItemModal               : showAddBacklogItemModal,
            showAddItemToSubMilestoneModal        : showAddItemToSubMilestoneModal,
            showAddSubmilestoneModal              : showAddSubmilestoneModal,
            showEditModal                         : showEditModal,
            showEditSubmilestoneModal             : showEditSubmilestoneModal,
            switchClosedMilestoneItemsViewMode    : switchClosedMilestoneItemsViewMode,
            switchViewMode                        : switchViewMode,
            thereAreClosedMilestonesLoaded        : thereAreClosedMilestonesLoaded,
            thereAreOpenMilestonesLoaded          : thereAreOpenMilestonesLoaded,
            getRestError                          : RestErrorService.getError,
            BACKLOG_ITEMS_PAGINATION        : { limit: 50, offset: 0 },
            OPEN_MILESTONES_PAGINATION      : { limit: 50, offset: 0 },
            CLOSED_MILESTONES_PAGINATION    : { limit: 50, offset: 0 },
            MILESTONE_CONTENT_PAGINATION    : { limit: 50, offset: 0 }
        });

        self.init();

        function init() {
            self.user_id               = SharedPropertiesService.getUserId();
            self.project_id            = SharedPropertiesService.getProjectId();
            self.milestone_id          = parseInt(SharedPropertiesService.getMilestoneId(), 10);
            self.use_angular_new_modal = SharedPropertiesService.getUseAngularNewModal();

            initViewModes(SharedPropertiesService.getViewMode());
            self.loadBacklog(SharedPropertiesService.getMilestone());
            self.loadInitialBacklogItems(SharedPropertiesService.getInitialBacklogItems());
            self.loadInitialMilestones(SharedPropertiesService.getInitialMilestones());
        }

        function initViewModes(view_mode) {
            self.current_view_class        = self.compact_view_key;
            self.current_closed_view_class = self.show_closed_view_key;

            if (view_mode) {
                self.current_view_class = view_mode;
            }
        }

        function switchViewMode(view_mode) {
            self.current_view_class = view_mode;
            UserPreferencesService.setPreference(
                self.user_id,
                'agiledashboard_planning_item_view_mode_' + self.project_id,
                view_mode
            );
        }

        function switchClosedMilestoneItemsViewMode(view_mode) {
            self.current_closed_view_class = view_mode;
        }

        function isMilestoneContext() {
            return ! isNaN(self.milestone_id);
        }

        function loadBacklog(initial_milestone) {
            if (! self.isMilestoneContext()) {
                BacklogService.loadProjectBacklog(self.project_id);

            } else {
                if (initial_milestone) {
                    MilestoneService.defineAllowedBacklogItemTypes(initial_milestone);
                    MilestoneService.augmentMilestone(
                        initial_milestone,
                        self.MILESTONE_CONTENT_PAGINATION.limit,
                        self.MILESTONE_CONTENT_PAGINATION.offset,
                        self.items
                    );

                    BacklogService.loadMilestoneBacklog(initial_milestone);
                } else {
                    MilestoneService.getMilestone(
                        self.milestone_id,
                        self.MILESTONE_CONTENT_PAGINATION.limit,
                        self.MILESTONE_CONTENT_PAGINATION.offset,
                        self.items
                    ).then(function(data) {
                        BacklogService.loadMilestoneBacklog(data.results);
                    });
                }
            }
        }

        function loadInitialBacklogItems(initial_backlog_items) {
            if (initial_backlog_items) {
                appendBacklogItems(initial_backlog_items.backlog_items_representations);

                self.BACKLOG_ITEMS_PAGINATION.offset = self.BACKLOG_ITEMS_PAGINATION.limit;
                self.backlog_items.fully_loaded      = self.BACKLOG_ITEMS_PAGINATION.offset >= initial_backlog_items.total_size;

            } else {
                displayBacklogItems();
            }
        }

        function displayBacklogItems() {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.when();
            }

            return self.fetchBacklogItems(self.BACKLOG_ITEMS_PAGINATION.limit, self.BACKLOG_ITEMS_PAGINATION.offset).then(function(total) {
                self.BACKLOG_ITEMS_PAGINATION.offset += self.BACKLOG_ITEMS_PAGINATION.limit;
                self.backlog_items.fully_loaded       = self.BACKLOG_ITEMS_PAGINATION.offset >= total;
            });
        }

        function backlogItemsAreLoadingOrAllLoaded() {
            return (self.backlog_items.loading || self.backlog_items.fully_loaded);
        }

        function fetchBacklogItems(limit, offset) {
            self.backlog_items.loading = true;
            var promise;

            if (self.isMilestoneContext()) {
                promise = BacklogItemService.getMilestoneBacklogItems(self.milestone_id, limit, offset);
            } else {
                promise = BacklogItemService.getProjectBacklogItems(self.project_id, limit, offset);
            }

            return promise.then(function(data) {
                var items = data.results;
                appendBacklogItems(items);

                return data.total;
            });
        }

        function appendBacklogItems(items) {
            _.extend(self.items, _.indexBy(items, 'id'));
            BacklogService.appendBacklogItems(items);
            BacklogService.filterItems(self.filter_terms);
        }

        function fetchAllBacklogItems(limit, offset) {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.reject();
            }

            return self.fetchBacklogItems(limit, offset).then(function(total) {
                if ((offset + limit) > total) {
                    self.backlog_items.fully_loaded = true;
                    return;
                } else {
                    return fetchAllBacklogItems(limit, offset + limit);
                }
            });
        }

        function filterBacklog() {
            self.fetchAllBacklogItems(self.BACKLOG_ITEMS_PAGINATION.limit, self.BACKLOG_ITEMS_PAGINATION.offset)
                ['finally'](function() {
                    BacklogService.filterItems(self.filter_terms);
                });
        }

        function loadInitialMilestones(initial_milestones) {
            if (initial_milestones) {
                _.forEach(initial_milestones.milestones_representations, function(milestone) {
                    MilestoneService.augmentMilestone(
                        milestone,
                        self.MILESTONE_CONTENT_PAGINATION.limit,
                        self.MILESTONE_CONTENT_PAGINATION.offset,
                        self.items
                    );
                });

                self.milestones.content                = initial_milestones.milestones_representations;
                self.OPEN_MILESTONES_PAGINATION.offset = self.OPEN_MILESTONES_PAGINATION.limit;

                if (self.OPEN_MILESTONES_PAGINATION.offset < initial_milestones.total_size) {
                    displayOpenMilestones();
                } else {
                    self.milestones.loading = false;
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
            self.milestones.loading = true;

            if (! self.isMilestoneContext()) {
                fetchClosedMilestones(self.project_id, self.CLOSED_MILESTONES_PAGINATION.limit, self.CLOSED_MILESTONES_PAGINATION.offset);
            } else {
                fetchClosedSubMilestones(self.milestone_id, self.CLOSED_MILESTONES_PAGINATION.limit, self.CLOSED_MILESTONES_PAGINATION.offset);
            }
        }

        function fetchOpenMilestones(project_id, limit, offset) {
            self.milestones.loading = true;

            return MilestoneService.getOpenMilestones(project_id, limit, offset, self.items).then(function(data) {
                var milestones          = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchOpenMilestones(project_id, limit, offset + limit);
                } else {
                    self.milestones.loading                      = false;
                    self.milestones.open_milestones_fully_loaded = true;
                }
            });
        }

        function fetchOpenSubMilestones(milestone_id, limit, offset) {
            self.milestones.loading = true;

            return MilestoneService.getOpenSubMilestones(milestone_id, limit, offset, self.items).then(function(data) {
                var milestones          = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchOpenSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    self.milestones.loading                      = false;
                    self.milestones.open_milestones_fully_loaded = true;
                }
            });
        }

        function fetchClosedMilestones(project_id, limit, offset) {
            self.milestones.loading = true;

            return MilestoneService.getClosedMilestones(project_id, limit, offset, self.items).then(function(data) {
                var milestones            = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchClosedMilestones(project_id, limit, offset + limit);
                } else {
                    self.milestones.loading                        = false;
                    self.milestones.closed_milestones_fully_loaded = true;
                }
            });
        }

        function fetchClosedSubMilestones(milestone_id, limit, offset) {
            self.milestones.loading = true;

            return MilestoneService.getClosedSubMilestones(milestone_id, limit, offset, self.items).then(function(data) {
                var milestones            = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, 'id').reverse();

                if ((offset + limit) < data.total) {
                    fetchClosedSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    self.milestones.loading                        = false;
                    self.milestones.closed_milestones_fully_loaded = true;
                }
            });
        }

        function getOpenMilestones() {
            return $filter('filter')(self.milestones.content, { semantic_status: 'open' });
        }

        function getClosedMilestones() {
            return $filter('filter')(self.milestones.content, { semantic_status: 'closed' });
        }

        function thereAreOpenMilestonesLoaded() {
            var open_milestones = getOpenMilestones();

            return open_milestones.length > 0;
        }

        function thereAreClosedMilestonesLoaded() {
            var closed_milestones = getClosedMilestones();

            return closed_milestones.length > 0;
        }

        function generateMilestoneLinkUrl(milestone, pane) {
            return '?group_id=' + self.project_id + '&planning_id=' + milestone.planning.id + '&action=show&aid=' + milestone.id + '&pane=' + pane;
        }


        function showAddBacklogItemModal($event, item_type) {
            $event.preventDefault();

            var compared_to;
            if (! _.isEmpty(self.backlog_items.content)) {
                compared_to = {
                    direction : "before",
                    item_id   : self.backlog_items.content[0].id
                };
            }

            var callback = function(item_id) {
                var promise;
                if (! self.isMilestoneContext()) {
                    if (compared_to) {
                        promise = ProjectService.removeAddReorderToBacklog(undefined, self.backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = ProjectService.removeAddToBacklog(undefined, self.backlog.rest_route_id, item_id);
                    }
                } else {
                    if (compared_to) {
                        promise = MilestoneService.removeAddReorderToBacklog(undefined, self.backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = MilestoneService.removeAddToBacklog(undefined, self.backlog.rest_route_id, item_id);
                    }
                }

                promise.then(function() {
                    var subpromise;
                    if (self.filter_terms) {
                        subpromise = prependItemToFilteredBacklog(item_id);
                    } else {
                        subpromise = prependItemToBacklog(item_id);
                    }
                    return subpromise;
                });

                return promise;
            };

            if (self.use_angular_new_modal) {
                var parent_item = (! _.isEmpty(self.current_milestone)) ? self.current_milestone : undefined;
                NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);

            } else {
                TuleapArtifactModalService.showCreateItemForm(item_type.id, self.backlog.rest_route_id, callback);
            }
        }

        function showEditSubmilestoneModal($event, submilestone) {
            var when_left_mouse_click = 1;

            if ($event.which === when_left_mouse_click) {
                $event.preventDefault();

                NewTuleapArtifactModalService.showEdition(
                    self.user_id,
                    submilestone.artifact.tracker.id,
                    submilestone.artifact.id,
                    self.refreshSubmilestone
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
                    _.forEach(self.milestones.content, function(milestone) {
                        submilestone_ids.push(milestone.id);
                    });

                    submilestone_ids.push(submilestone_id);

                    var promise = MilestoneService.putSubMilestones(self.backlog.rest_route_id, submilestone_ids);
                    promise.then(function() {
                        return prependSubmilestoneToSubmilestoneList(submilestone_id);
                    });

                    return promise;
                }
            };

            var parent_item = (! _.isEmpty(self.current_milestone)) ? self.current_milestone : undefined;
            NewTuleapArtifactModalService.showCreation(submilestone_type.id, parent_item, callback);
        }

        function prependSubmilestoneToSubmilestoneList(submilestone_id) {
            return MilestoneService.getMilestone(
                submilestone_id,
                self.MILESTONE_CONTENT_PAGINATION.limit,
                self.MILESTONE_CONTENT_PAGINATION.offset,
                self.items
            ).then(function(data) {
                self.milestones.content.unshift(data.results);
            });
        }

        function showAddItemToSubMilestoneModal(item_type, parent_item) {
            var compared_to;
            if (! _.isEmpty(parent_item.content)) {
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
                return BacklogItemCollectionService.refreshBacklogItem(item_id).then(function() {
                    if (milestone) {
                        MilestoneService.updateInitialEffort(milestone);
                    }
                });
            };

            if ($event.which === when_left_mouse_click) {
                $event.preventDefault();

                NewTuleapArtifactModalService.showEdition(
                    self.user_id,
                    backlog_item.artifact.tracker.id,
                    backlog_item.artifact.id,
                    callback
                );
            }
        }

        function prependItemToSubmilestone(child_item_id, parent_item) {
            return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
                self.items[child_item_id] = data.backlog_item;

                parent_item.content.unshift(data.backlog_item);
                MilestoneService.updateInitialEffort(parent_item);
            });
        }

        function prependItemToBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                self.items[backlog_item_id] = new_item;
                self.backlog_items.content.unshift(new_item);
                self.backlog_items.filtered_content.unshift(new_item);
            });
        }

        function prependItemToFilteredBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                self.items[backlog_item_id] = new_item;
                self.backlog_items.content.unshift(new_item);
            });
        }

        function refreshSubmilestone(submilestone_id) {
            var submilestone = _.find(self.milestones.content, { 'id': submilestone_id });

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

        function canShowBacklogItem(backlog_item) {
            if (typeof backlog_item.isOpen === 'function') {
                return backlog_item.isOpen() || self.current_closed_view_class === self.show_closed_view_key;
            }

            return true;
        }

        function hideUserCantPrioritizeForMilestones() {
            if (self.milestones.content.length === 0) {
                return true;
            }

            return self.milestones.content[0].has_user_priority_change_permission;
        }

        function displayUserCantPrioritizeForMilestones() {
            return ! hideUserCantPrioritizeForMilestones();
        }

        function getInitialEffortMessage(initial_effort) {
            return gettextCatalog.getPlural(initial_effort, "pt", "pts");
        }
    }
})();
