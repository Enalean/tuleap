var TreeTable = (function(treeTableId) {
	var treeTable = Class.create({
		
		initialize : function(rootId) {
			this.rootId = rootId;
			Event.observe(window, 'load', this.load.bind(this));
		},
		
		load : function() {
			this.root = $(this.rootId);
			this.collapseAll();
			function _eventOnNode(event) {
				this.toggleCollapse(Event.element(event).up('TR'));
				Event.stop(event);
			}
			$A(this.root.getElementsByClassName('node-tree')).invoke('observe', 'click', _eventOnNode.bind(this), this);
			$A(this.root.getElementsByClassName('node-content')).invoke('observe', 'dblclick', _eventOnNode.bind(this), this);
			this.expandAll();
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
		
		collapseImg: function(TRElement) {
			var nodeTree = TRElement.getElementsByClassName('node-tree');
			if (nodeTree.length > 0) {
				nodeTree[0].setStyle({backgroundImage:'url(' + codendi.imgroot + '/ic/toggle-small.png)'});
			}
		},
		
		expandImg: function(TRElement) {
			var nodeTree = TRElement.getElementsByClassName('node-tree');
			if (nodeTree.length > 0) {
				nodeTree[0].setStyle({backgroundImage: 'url(' + codendi.imgroot + '/ic/toggle-small-expand.png)'});
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
		
		collapse: function(TRElement) {
			var nodeChild = this.getNodeChild(TRElement);
			if (nodeChild) {
				var TRHeight = TRElement.getHeight();
				if ( typeof TRHeight != "number") {
					TRHeight = TRHeight.match(/[0-9]+/);
				}
				TRHeight -= nodeChild.getHeight();
				TRHeight += "px";
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
			TRElement.setStyle({whiteSpace:'nowrap'});
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
		
		collapseAll: function() {
			this.root.getElementsBySelector('TR').each(this.collapse, this);
			return this;
		},
		
		expandAll: function() {
			this.root.getElementsBySelector('TR').each(this.expand, this);
			return this;
		}
	});
	
	return new treeTable(treeTableId);
	
})('treeTable');
