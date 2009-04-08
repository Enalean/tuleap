	<script type="text/javascript">
			var elements = new Array;
			function enable(id) {
				_disable(id, false)
			}
			function disable(id) {
				_disable(id, true)
			}
			function _disable(id, disable) {
				el = document.getElementById(id);
				if (el) {
					if (disable) {
						el.className = el.className.replace(/enabled/, 'disabled');
					} else {
						el.className = el.className.replace(/disabled/, 'enabled');
					}
					if (elements[id]) {
						for(i = 0 ; i < elements[id].length ; i++) {
							el = document.getElementById(elements[id][i]);
							if (el) {
								el.disabled = disable;
								el.readonly = disable;
							}
						}
					}
				}
			}
			// {{{ Validator
			function Validator() {
				this._errors = false;
			}
			with (Validator) {
				prototype.valide = function() {
					return true;
				}
				prototype.errors = function() {
					return this._errors;
				}
				prototype.reset = function() {
					this._errors = false;
				}
			};
			// }}}
			
			var validators = new Array;
			function validate() {
				var errors = '';
				for (i = 0 ; i < validators.length ; i++) {
					if (!(validators[i].valide())) {
						errors += '- '+validators[i].errors()+"\n";
						validators[i].reset();
					}
				}
				if (errors == '') {
					return true;
				} else {
					alert(errors);
					return false;
				}
			}
		</script>
	<form action="?" method="POST">
		<div id="container">
			<div id="top">
				<?=$top?>
			</div>
			<div id="content">
				<?=$content?>
			</div>
			<div id="footer">
				<?=$footer?>
			</div>
		</div>
	</form>
