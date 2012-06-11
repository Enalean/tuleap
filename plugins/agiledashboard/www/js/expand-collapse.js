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

    getNodeChild: function(nodeElement) {
        if(nodeElement) {
            var nodeChild = nodeElement.select('.planning-item');
            if (nodeChild[0]) {
                return nodeChild[0];
            }
        }
        return false
    },

    collapseAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.collapse, this);
        return this;
    },

    expandAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.expand, this);
        return this;
    },

    isExpanded: function(TRElement) {
        var nodeChild = this.getNodeChild(TRElement);
        return nodeChild && (nodeChild.visible());
    },

    toggleCollapse: function(TRElement) {
        if(this.isExpanded(TRElement)) {
            this.collapse(TRElement);
        } else {
            this.expand(TRElement);
        }
    },

    collapse: function(TRElement) {
        this.hideChildren(TRElement);
        this.toggleLink(TRElement);
        return this;
    },
    
    hideChildren: function(nodeElement) {
        this.children(nodeElement).each(function(childNodeElement) {
            childNodeElement.hide();
        });
    },
    
    toggleLink: function(nodeElement) {
        nodeElement.down('.toggle-collapse').update(this.getLinkText(nodeElement));
    },
    
    getLinkText: function(nodeElement) {
        if (this.hasNoChildren(nodeElement)) {
            return '';
        }
        
        if(this.isExpanded(nodeElement)) {
            return '-';
        } else {
            return '+';
        }
    },

    hasNoChildren: function(nodeElement) {
        return !this.getNodeChild(nodeElement)
    },
    
    expand: function(TRElement) {
        this.children(TRElement).each(function(planningItem) {
            planningItem.show();
        });
        this.toggleLink(TRElement);
        return this;
    },
    
    children: function(nodeElement) {
        var firstChildNode  = nodeElement.down('.planning-item');
        
        if (firstChildNode) {
            var otherChildNodes = firstChildNode.siblings();
            return $A([firstChildNode].concat(otherChildNodes));
        } else {
            return [];
        }
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
