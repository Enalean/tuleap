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
    
    collapseAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.collapse, this);
        return this;
    },

    expandAll: function() {
        this.root.getElementsBySelector('.planning-item').each(this.expand, this);
        return this;
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

    children: function(nodeElement) {
        var firstChildNode  = nodeElement.down('.planning-item');
        
        if (firstChildNode) {
            var otherChildNodes = firstChildNode.siblings();
            return $A([firstChildNode].concat(otherChildNodes));
        } else {
            return [];
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
