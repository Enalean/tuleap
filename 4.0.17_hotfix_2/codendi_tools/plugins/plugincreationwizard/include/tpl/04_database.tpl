				<h2>Database</h2>
				<input type="checkbox" name="create_db" id="create_db" onclick="this.checked?enable('create_db_options'):disable('create_db_options')"/><label for="create_db">Create <code>db</code> directory</label>
				<div id="create_db_options" class="disabled">
					<div style="float:left">
						<div><label for="install">Paste here your <code>install sql</code> statements</label></div>
						<textarea name="install" disabled="disabled" id="install" rows="10" cols="50"></textarea>
					</div>
					<div>
						<div><label for="uninstall">Paste here your <code>uninstall sql</code> statements</label></div>
						<textarea name="uninstall" disabled="disabled" id="uninstall" rows="10" cols="50"></textarea>
					</div>
				</div>
				<script type="text/javascript">
					elements['create_db_options'] = new Array('install', 'uninstall');
				</script>

