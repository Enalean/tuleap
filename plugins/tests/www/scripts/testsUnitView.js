(function() {

	var baseSrc  = 'themes/default/images/';
	var minusSrc = baseSrc + 'minus.png';
	var plusSrc  = baseSrc + 'plus.png';
	var currentCategory = 0;
	
	function uncheck(category) {
		var inputBox = $(category).down('input[type=checkbox]');
		if (inputBox) {
			inputBox.checked = false;
			uncheck(category.up('li.category'));
		}
	}
	
	function register_events(category) {
		var inputBoxes = Form.getInputs(category, 'checkbox');
		inputBoxes.each(function(inputBox){
			Event.observe(inputBox, 'change', (function (evt) {
				var checked = this.checked;
				console.log(this.up('li'));
				this.up('li').select('input[type=checkbox]').each(function (input) {
                    input.checked = checked;
				});
				//On remonte
				if (!checked && this.parentNode.id != 'menu') {
					uncheck(this.up('li.category'));
				}
			}).bind(inputBox));
		});
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
	
	function initCategory(category) {
		Event.observe($('plus_' + currentCategory), 'click', function (evt) {
			category.getElementsBySelector('ul').invoke('toggle');
			var treeImg = category.down('img');
			if (treeImg.src.indexOf(minusSrc) > 0) {
				treeImg.src = plusSrc;
			} else {
				treeImg.src = minusSrc;
			}
			Event.stop(evt);
			return false;
		});
	}
	
	function init() {
		$$('li.category').each(function(category){
			register_events(category);
			currentCategory++;
			new Insertion.Top(category, '<img id="plus_' + currentCategory +'" src="' + minusSrc +'" />');
			initCategory(category);
		});
		
		submitPanelEffect();
	}
	Event.observe(window, 'load', init, true);
})();