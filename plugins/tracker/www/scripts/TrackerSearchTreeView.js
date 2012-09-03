var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };
codendi.tracker.crossSearch = codendi.tracker.crossSearch || { };

/**
 * Add expand/collapse behaviour on a table element given by this ID in constructor.
 * The table element must have :
 *  - the firsts TD of each row of class .first-column
 *  - inside the first TD :
 *      * spans with nbsp; foreach indent (used by getLevel to know the level of a tr)
 *      * spans of classes node-tree to fire the expand/collapse event.
 *      * a div of class node-content that contains the "normal" content of the TD
 *          + eventually a span of class node-child if it has children
 */
codendi.tracker.crossSearch.TreeTable = Class.create({
    /**
     * Called when object is constructed
     */
    initialize : function(root) {
        this.root = $(root);
        if (this.root !== null ) {
            /* private method binded as event listener */
            function _eventOnNode(event) {
                this.toggleCollapse(Event.element(event).up('TR'));
                Event.stop(event);
            };
            this.collapseAll();
            this.root.select('.node-tree').invoke('observe', 'click', _eventOnNode.bindAsEventListener(this));
            this.root.select('.node-content').invoke('observe', 'dblclick', _eventOnNode.bindAsEventListener(this));
            this.insertTreeViewActions();
        }
    },
    
    insertTreeViewActions: function() {
        var expandAllLink = this.link('expand_all', function(event) {
            this.expandAll();
            Event.stop(event);
        });
        var collapseAllLink = this.link('collapse_all', function(event) {
            this.collapseAll();
            Event.stop(event);
        });
        
        var treeViewActionsContainer = this.root.previous('.tree-view-actions');
        treeViewActionsContainer.insert(expandAllLink);
        treeViewActionsContainer.insert(' / ');
        treeViewActionsContainer.insert(collapseAllLink);
    },
    
    link: function(textKey, func) {
        var text = codendi.getText('tracker_crosssearch', textKey);
        return new Element('a', {href: '#'}).update(text).observe('click', func.bind(this));
    },

    getChildren: function(tr) {
        var children     = $A();
        if (!tr) return children;
        var myLevel      = this.getLevel(tr);
        var currentEl    = tr.next('TR');
        var currentLevel = this.getLevel(currentEl);
        while (currentLevel > myLevel) {
            if (currentLevel == myLevel + 1) {
                children.push(currentEl);
            }
            currentEl    = currentEl.next('TR');
            currentLevel = this.getLevel(currentEl);
        }
        return children;
    },

    hasChildren: function(tr) {
        return tr.down('.node-child');
    },

    getNodeChild: function(tr) {
        if(tr) {
            return tr.down('.node-child');
        }
    },

    getLevel: function(tr) {
        var numSpan = 0;
        if (tr) {
            var curEl = tr.down('TD');
            if (curEl) {
                curEl = curEl.down('SPAN');
                while (curEl) {
                    numSpan++;
                    curEl = curEl.next('SPAN');
                }
                return numSpan / 2;
            }
        }
        return numSpan;
    },

    collapseAll: function() {
        this.root.down('tbody').childElements().each(this.collapse, this);
        return this;
    },

    expandAll: function() {
        this.root.getElementsBySelector('TR').each(this.expand, this);
        return this;
    },

    isCollapsed: function(tr) {
        var nodeChild = this.getNodeChild(tr);
        if (nodeChild) {
            return nodeChild.visible() == false;
        }
        return false;
    },

    toggleCollapse: function(tr) {
        if(this.isCollapsed(tr)) {
            this.expand(tr);
        } else {
            this.collapse(tr);
        }
    },

    setNodeTreeImage: function(tr, NodeTreeImage) {
        var nodeTree = tr.down('.node-tree');
        if (nodeTree) {
            nodeTree.setStyle({backgroundImage:'url(' + codendi.imgroot + NodeTreeImage + ')'});
        }
    },

    collapseImg: function(tr) {
        this.setNodeTreeImage(tr, '/ic/toggle-small.png');
    },

    expandImg: function(tr) {
        this.setNodeTreeImage(tr, '/ic/toggle-small-expand.png');
    },

    collapse: function(tr) {
        var nodeChild = this.getNodeChild(tr);
        if (nodeChild) {
            nodeChild.hide();
            var children = this.getChildren(tr);
            children.each(function(child) {
                this.hide(child);
            }, this);
            this.expandImg(tr);
        }
        return this;
    },

    expand: function(tr) {
        var nodeChild = this.getNodeChild(tr);
        if (nodeChild) {
            nodeChild.show();
            this.getChildren(tr).invoke('show');
            this.collapseImg(tr);
        }
        return this;
    },

    hide: function(tr) {
        // We are hiding sub children recursively. therefore we dont need to
        // wait for all children to be hidden before giving hand to the
        // user (else we block the browser).
        //
        // solution: start a new 'thread'. This is done with setTimeout.
        //
        // we need to store a reference to the current object for setTimeout:
        //
        // > The function that was passed as the first parameter will get
        // > called by the global object, which means that this inside the
        // > called function refers to that very object
        // -- http://bonsaiden.github.com/JavaScript-Garden/#other.timeouts
        //
        var that = this;
        setTimeout(function () {
            that.collapse(tr);
            tr.hide();
        }, 0);
    }
});

Event.observe(window, 'load', function() {
    $$('.tree-view').each(function (element) {
        new codendi.tracker.crossSearch.TreeTable(element);
    })
});
