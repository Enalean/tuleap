				<h2>Descriptor</h2>
				<div id="choices">
					<table>
						<tr>
							<td colspan="2"><b>System names</b></td>
						</tr>
						<tr>
							<td><label for="class_name">Class Name</label></td>
							<td><input type="text" size="20" id="class_name" name="class_name" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
							    if (document.getElementById('class_name').value.length == 0) {
								    this._errors = 'The class name cannot be empty';
								} else {
									if (document.getElementById('class_name').value.match(/[^a-zA-Z0-9_]/)) {
										this._errors = 'The class name must contain only the following characters: a-z, A-Z, 0-9 and _';
									} else {
										if (document.getElementById('class_name').value.match(/^[0-9].*/)) {
											this._errors = 'The class name must not begin by a digit';
										}
									}
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
						<tr>
							<td colspan="2"><b>Descriptor</b></td>
						</tr>
						<tr>
							<td><label for="version">Version</label></td>
							<td><input type="text" size="10" id="version" name="version" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
								if (document.getElementById('version').value.length == 0) {
									this._errors = 'The version cannot be empty';
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
						<tr>
							<td><label for="descriptor_name_en_US">Name (en_US)</label></td>
							<td><input type="text" size="30" id="descriptor_name_en_US" name="descriptor_name[en_US]" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
								if (document.getElementById('descriptor_name_en_US').value.length == 0) {
									this._errors = 'The name en_US cannot be empty';
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
						<tr>
							<td><label for="descriptor_name_fr_FR">Name (fr_FR)</label></td>
							<td><input type="text" size="30" id="descriptor_name_fr_FR" name="descriptor_name[fr_FR]" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
								if (document.getElementById('descriptor_name_fr_FR').value.length == 0) {
									this._errors = 'The name fr_FR cannot be empty';
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
						<tr>
							<td><label for="descriptor_description_en_US">Description (en_US)</label></td>
							<td><input type="text" size="50" id="descriptor_description_en_US" name="descriptor_description[en_US]" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
								if (document.getElementById('descriptor_description_en_US').value.length == 0) {
									this._errors = 'The description en_US cannot be empty';
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
						<tr>
							<td><label for="descriptor_description_fr_FR">Description (fr_FR)</label></td>
							<td><input type="text" size="50" id="descriptor_description_fr_FR" name="descriptor_description[fr_FR]" /><script type="text/javascript">
							v = new Validator();
							v.valide = function() {
								if (document.getElementById('descriptor_description_fr_FR').value.length == 0) {
									this._errors = 'The description fr_FR cannot be empty';
								}
								return this._errors == false;
							}
							validators[validators.length] = v;
							</script></td>
						</tr>
					</table>
				</div>

