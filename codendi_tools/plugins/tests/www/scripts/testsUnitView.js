(function() {
	function uncheck(element) {
		if (element && element.id != 'menu') {
			var len = element.childNodes.length;
			var found = false;
			for (var i = 0 ; i < len && !found; ++i) {
				if (element.childNodes[i].tagName == 'INPUT' && element.childNodes[i]['type'] == 'checkbox') {
					element.childNodes[i].checked = false;
					found = true;
				}
			}
			uncheck(element.parentNode);
		}
	}
	function register_events(element) {
		if (element.childNodes) {
			$A(element.childNodes).each(function (child) {
				var found = false;
				if (child.tagName == 'INPUT' && child['type'] == 'checkbox') {
					Event.observe(child, 'change', (function (evt) {
						var checked = this.checked;
						var col = this.parentNode.getElementsByTagName('input');
						var len = col.length;
						for (var i = 0 ; i < len ; ++i) {
							if (col[i]['type'] == 'checkbox') {
								col[i].checked = checked;
							}
						}
						//On remonte
						if (!checked && this.parentNode.id != 'menu') {
							uncheck(this.parentNode.parentNode.parentNode);
						}
					}).bind(child));
					found = true;
				} else {
					register_events(child);
				}
			});
		}
	}
	
	function submitPanelEffect() {
	    var submitInput = $('submit_panel').down('input');
	    if (submitInput) {
	    	var panelHeight = $('submit_panel').up('td').offsetHeight;
	        document.observe('mouseover', function() {
	            if (submitInput.offsetHeight != panelHeight) {
	                submitInput.setStyle({
	                    height:panelHeight + 'px',
	                });
	            }
	        });
	    }
	}
	
	function init() {
		var plus = 0;
		$$('li.categ').each(function (element) {
			register_events(element);
			plus++;
			new Insertion.Top(element, '<a href="" id="plus_' + plus +'"><img src="themes/default/images/minus.png" /></a>');
			var uls = $A(element.childNodes).findAll(function (element) {
				return element.tagName == 'ul';
			});
			var matchPlus = new RegExp("plus.png$");
			Event.observe($('plus_'+plus), 'click', function (evt) {
				uls.each(function (element) {
					Element.toggle(element);
				});
				if (Event.element(evt).src.match(matchPlus)) {
					Event.element(evt).src = 'themes/default/images/minus.png';
				} else {
					Event.element(evt).src = 'themes/default/images/plus.png';
				}
				Event.stop(evt);
				return false;
			});
		});
		submitPanelEffect();
	}
	Event.observe(window, 'load', init, true);
})()