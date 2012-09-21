var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };
tuleap.agiledashboard.planning = tuleap.agiledashboard.planning || { };

tuleap.agiledashboard.planning.TreeView = Class.create({
    initialize: function(root, nodeSelector) {
        this.root         = $(root);
        this.nodeSelector = nodeSelector;
        this.linkSelector = '.toggle-collapse';
        
        if (this.root !== null ) {
            this.expandUntilDraggable();
            this.root.select(this.nodeSelector + ' ' + this.linkSelector).invoke('observe', 'click', this._eventOnNode.bindAsEventListener(this));
        }
    },
    // Handle event observe for expand/collapse
    _eventOnNode: function (event) {
        this.toggleCollapse(Event.element(event).up(this.nodeSelector));
        Event.stop(event);
    },
    collapseAll: function() {
        this.root.getElementsBySelector(this.nodeSelector).each(this.collapse, this);
        return this;
    },

    expandAll: function() {
        this.root.getElementsBySelector(this.nodeSelector).each(this.expand, this);
        return this;
    },

    expandUntilDraggable: function() {
        this.collapseAll();
        this.root.select('.planning-draggable').each( function(draggable) {
            this.expandParent(draggable);
        }.bind(this));
    },

    expandParent: function (item) {
        var parent = $(item).up(this.nodeSelector);
        if (parent && !this.isExpanded(parent)) {
            this.expand(parent);
            this.expandParent(parent);
        }
    },

    collapse: function(nodeElement) {
        this.children(nodeElement).each(function(childNodeElement) {
            childNodeElement.hide();
        });
        this.toggleLink(nodeElement);
        return this;
    },

    expand: function(nodeElement) {
        this.children(nodeElement).each(function(childNodeElement) {
            childNodeElement.show();
        });
        this.toggleLink(nodeElement);
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
    
    toggleLink: function(nodeElement) {
        nodeElement.down(this.linkSelector).update(this.getLinkText(nodeElement));
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

    children: function(nodeElement) {
        var firstChildNode  = nodeElement.down(this.nodeSelector);
        
        if (firstChildNode) {
            var otherChildNodes = firstChildNode.siblings();
            return $A([firstChildNode].concat(otherChildNodes));
        } else {
            return [];
        }
    },
    
    getNodeChild: function(nodeElement) {
        if(nodeElement) {
            var nodeChild = nodeElement.select(this.nodeSelector);
            if (nodeChild[0]) {
                return nodeChild[0];
            }
        }
        return false
    }
    
});

Event.observe(window, 'load', function() {
    $$('.planning-backlog .backlog-content', '.release_planner').each(function (element) {
        new tuleap.agiledashboard.planning.TreeView(element, '.planning-item');
    });
});
