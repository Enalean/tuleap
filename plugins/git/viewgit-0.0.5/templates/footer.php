<?php
if ($conf['ad']) {
	echo "<div class=\"ad\"><a href=\"http://viewgit.sourceforge.net/\" title=\"Visit the ViewGit homepage\">ViewGit</a></div>";
}
VGPlugin::call_hooks('footer');
?>
</div>
</body>
</html>
