<?php
/**
 * Google Analytics plugin for ViewGit.
 *
 * This adds a Google Analytics snippet for each page, if
 * $conf['google_analytics'] is set.
 *
 * @author Heikki Hokkanen <hoxu@users.sf.net>
 */
class AnalyticsPlugin extends VGPlugin
{
	function __construct() {
		$this->register_hook('footer');
	}

	function hook($type) {
		global $conf;

		if ($type == 'footer' && isset($conf['google_analytics'])) {
			$this->output(
"
<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"$conf[google_analytics]\");
pageTracker._trackPageview();
} catch(err) {}</script>
");
		}
	}
}

