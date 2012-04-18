(function() {
	/**
	 * Add expand/collapse behaviour on a table element given by this ID in constructor.
	 * The table element must have :
	 *  - the firsts TD of each row of class .first-column
	 *  - inside the first TD :
	 *  	* spans with nbsp; foreach indent (used by getLevel to know the level of a tr)
	 *  	* spans of classes node-tree to fire the expand/collapse event. 
	 *  	* a div of class node-content that contains the "normal" content of the TD 
	 *  		+ eventually a span of class node-child if it has children 
	 */
	var treeTable = Class.create({
		/**
		 * Called when object is constructed
		 */
		initialize : function(rootId) {
			this.root = $(rootId);
			if (this.root !== null ) {
				/* private method binded as event listener */
				function _eventOnNode(event) {
					this.toggleCollapse(Event.element(event).up('TR'));
					Event.stop(event);
				};
				this.collapseAll();
				$A(this.root.getElementsByClassName('node-tree')).invoke('observe', 'click', _eventOnNode.bindAsEventListener(this));
				$A(this.root.getElementsByClassName('node-content')).invoke('observe', 'dblclick', _eventOnNode.bindAsEventListener(this));
				this.expandAll();
			}
		},
		
		getChildren: function(TRElement) {
			var children     = $A();
			if (!TRElement) return children;
			var myLevel      = this.getLevel(TRElement);
			var currentEl    = TRElement.next('TR');
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
		
		hasChildren: function(TRElement) {
			return TRElement.getElementsByClassName('node-child').length > 0;
		},
		
		getNodeChild: function(TRElement) {
			if(TRElement) {
				var nodeChild = TRElement.getElementsByClassName('node-child');
				if (nodeChild[0]) {
					return nodeChild[0];
				}
			}
		},
		
		getLevel: function(TRElement) {
			var numSpan = 0;
			if (TRElement) {
				var curEl = TRElement.down('TD');
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
			this.root.getElementsBySelector('TR').each(this.collapse, this);
			return this;
		},
		
		expandAll: function() {
			this.root.getElementsBySelector('TR').each(this.expand, this);
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
		
		setNodeTreeImage: function(TRElement, NodeTreeImage) {
			var nodeTree = TRElement.getElementsByClassName('node-tree');
			if (nodeTree.length > 0) {
				nodeTree[0].setStyle({backgroundImage:'url(' + codendi.imgroot + NodeTreeImage + ')'});
			}
		},
		
		collapseImg: function(TRElement) {
			this.setNodeTreeImage(TRElement, '/ic/toggle-small.png');
		},
		
		expandImg: function(TRElement) {
			this.setNodeTreeImage(TRElement, '/ic/toggle-small-expand.png');
		},
		
		collapse: function(TRElement) {
			var nodeChild = this.getNodeChild(TRElement);
			if (nodeChild) {
				var TRHeight = this._getHeight(TRElement) - this._getHeight(nodeChild) + 'px';
				nodeChild.hide();
				var children = this.getChildren(TRElement);
				children.each(function(child) {
					this.hide(child);
				}, this);
				this.expandImg(TRElement);
				TRElement.setStyle({height: TRHeight});
			}
			return this;
		},
		
		expand: function(TRElement) {
			var nodeChild = this.getNodeChild(TRElement);
			if (nodeChild) {
				nodeChild.show();
				this.getChildren(TRElement).each(this.show, this);
				this.collapseImg(TRElement);
			}
			return this;
		},
		
		hide: function(TRElement) {
			this.collapse(TRElement);
			TRElement.hide();
		},
		
		show: function(TRElement) {
			TRElement.show();
		},
		
		_getHeight: function(HTMLElement) {
			var ElementHeight = HTMLElement.getHeight();
			if ( typeof ElementHeight != 'number') {
				ElementHeight = ElementHeight.match(/[0-9]+/);
			}
			return ElementHeight;
		}
	});
	
	/**
	 * 
	 */
	Event.observe(window, 'load', function() {
		new treeTable('treeTable');
	});
	
})();
