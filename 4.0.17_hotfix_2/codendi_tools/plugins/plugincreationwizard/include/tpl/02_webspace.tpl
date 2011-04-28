				<h2>Web Space</h2>
				<input type="checkbox" checked="checked" name="use_web_space" id="use_web_space" onclick="this.checked?enable('use_web_space_options'):disable('use_web_space_options')"/><label for="use_web_space">Use web space</label>
				<div id="use_web_space_options" class="enabled">
					<div><input type="checkbox" checked="checked" name="use_mvc" id="use_mvc" /><label for="use_mvc">Use MVC</label></div>
					<div><input type="checkbox" checked="checked" name="use_css" id="use_css" /><label for="use_css">Use style.css</label></div>
				</div>
				<script type="text/javascript">
					elements['use_web_space_options'] = new Array('use_mvc', 'use_css');
				</script>

