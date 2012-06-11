var codendi = codendi || { };
codendi.agiledashboard = codendi.agiledashboard || { };
codendi.agiledashboard.planning = codendi.agiledashboard.planning || { };

codendi.agiledashboard.planning.TreeView = Class.create({
    initialize : function(root) {
        this.root = $(root);
        if (this.root !== null ) {
            /* private method binded as event listener */
            function _eventOnNode(event) {
                this.toggleCollapse(Event.element(event).up('.planning-item'));
                Event.stop(event);
            };
            this.collapseAll();
            this.root.select('.planning-item .toggle-collapse').invoke('observe', 'click', _eventOnNode.bindAsEventListener(this));
        }
    },

    getNodeChild: function(TRElement) {
        if(TRElement) {
            var nodeChild = TRElement.select('.planning-item');
            if (nodeChild[0]) {
                return nodeChild[0];
            }
        }
    },
    
    collapseAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.collapse, this);
        return this;
    },

    expandAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.expand, this);
        return this;
    },

    isCollapsed: function(TRElement) {
        var nodeChild = this.getNodeChild(TRElement);
        if (nodeChild) {
            return nodeChild.visible() == false;
        }
        return false;
    },

    toggleCollapse: function(TRElement) {
        if(this.isCollapsed(TRElement)) {
            this.expand(TRElement);
        } else {
            this.collapse(TRElement);
        }
    },

    collapse: function(TRElement) {
        TRElement.getElementsBySelector('.planning-item').each(function(planningItem) {
            planningItem.hide();
        });
        return this;
    },

    expand: function(TRElement) {
        TRElement.getElementsBySelector('.planning-item').each(function(planningItem) {
            planningItem.show();
        });
        return this;
    }
});

Event.observe(window, 'load', function() {
    $$('.planning-backlog .backlog-content').each(function (element) {
        new codendi.agiledashboard.planning.TreeView(element);
    });
    
    $$('.release_planner').each(function (element) {
        new codendi.agiledashboard.planning.TreeView(element);
    });
});
