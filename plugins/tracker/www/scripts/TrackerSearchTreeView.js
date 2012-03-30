var TreeTable = (function(treeTableId){
	var treeTable = {
			root : null,
			_setRoot : function (root) {
				this.root = root;
			},
			
			_defineChildren: function() {
				
				function _hasChildren() {
					return this.collapsed || this.getElementsByClassName('node-child').length > 0;
				};
				
				function _getLevel() {
					var numSpan = 0;
					var currentEl = this.down('TD');
					if (currentEl) {
						currentEl = currentEl.down('SPAN');
					} else {
						return 0;
					}
					while (currentEl) {
						numSpan++;
						currentEl = currentEl.next('SPAN');
					}
					return numSpan / 2;
				}
				
				function _collapse() {
					var nodeChild = this.getElementsByClassName('node-child');
					if (nodeChild.length > 0) {
//						nodeChild[0].hide();
						new Effect.Fade(nodeChild[0],{'duration': 0.25});
						var children = this.getChildren();
						children.each(function(child) {
							child.collapse();
//							child.hide();
							new Effect.Fade(child,{'duration': 0.25});
						});
						this.collapsed = true;
					} else {
						this.collapsed = false;
					}
					return this;
				}
				
				function _expand() {
					var nodeChild = this.getElementsByClassName('node-child');
					if (nodeChild.length > 0) {
//						nodeChild[0].show();
						new Effect.Appear(nodeChild[0],{'duration': 0.25});
						var children = this.getChildren();
						children.each(function(child) {
//							child.show();
							new Effect.Appear(child,{'duration': 0.25});
						});
					}
					this.collapsed = false;
					return this;
				}
				
				function _getChildren() {
					var myLevel      = this.getLevel();
					var children     = $A();
					var currentEl    = this.next('TR');
					var currentLevel = currentEl ? currentEl.getLevel() : 0;
					while (currentLevel > myLevel) {
						if (currentLevel == myLevel + 1) {
							children.push(currentEl);
						}
						currentEl    = currentEl.next('TR');
						currentLevel = currentEl ? currentEl.getLevel() : 0;
					}
					return children;
				}
				
				function _toggleCollapse() {
					if(this.collapsed) {
						this.expand();
					} else {
						this.collapse();
					}
					return this;
				}

				var TRElements = this.root.getElementsBySelector('TR');
				TRElements.each(function(TRElement) {
					TRElement.getLevel       = _getLevel.bind(TRElement);
					TRElement.getChildren    = _getChildren.bind(TRElement);
					TRElement.hasChildren    = _hasChildren.bind(TRElement);
					TRElement.collapse       = _collapse.bind(TRElement);
					TRElement.expand         = _expand.bind(TRElement);
					TRElement.toggleCollapse = _toggleCollapse.bind(TRElement);
				});
			},
			
			collapse:function() {
				this.root.getElementsBySelector('TR').invoke('collapse');
				return this;
			},
	};
	
	function treeTableLoad() {
		treeTable._setRoot($(treeTableId));
		treeTable.root.hide();
		treeTable._defineChildren();
		treeTable.collapse();
		function _eventOnNode(event) {
			Event.element(event).up('TR').toggleCollapse();
			Event.stop(event);
		}
		$A(TreeTable.root.getElementsByClassName('node-tree')).invoke('observe', 'click', _eventOnNode);
		$A(TreeTable.root.getElementsByClassName('node-content')).invoke('observe', 'dblclick', _eventOnNode);
	    new Effect.Appear(treeTable.root,{queue:'end', duration: 0.5});
	}
	
	Event.observe(window, 'load', treeTableLoad);
	return treeTable;
	
})('treeTable');
