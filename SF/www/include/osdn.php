<?php

util_get_content('layout/osdn_sites');

function osdn_nav_dropdown() {
    GLOBAL $osdn_sites;
// LJ write the FORM directly instead of using
// document.write because Netscape 4.x gets crazy
// and doesn't know how to redraw the page when 
// window is resized.

    if (session_issecure()) 
	$server = 'https://'.$GLOBALS['sys_https_host'];
    else
	$server = 'http://'.$GLOBALS['sys_default_domain'];
?>
	<!-- OSDN navdropdown -->
	    <script language=javascript>
	    function handle_navbar(index,form) {
	        if ( index > 1 ) {
	            window.location=form.options[index].value;
	        }
	    }
	    </script>
        <form name=form1>

        <a href="<?php echo $server;?>"><?php echo html_image("codex_logo.png",array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0")); ?></A><br>
        <select name=navbar onChange="handle_navbar(selectedIndex,this)">
        <option>Network Gallery</option>
        <option>------------</option>
<?php
        reset ($osdn_sites);
        while (list ($key, $val) = each ($osdn_sites)) {
        	list ($key, $val) = each ($val);
		print "\n   <option value=\"$val\">$key</option>";
        }
?>
        </select>
        </form>

        <noscript>
        <a href="<?php print $GLOBALS['sys_default_domain']; ?>"><img src="'.util_get_image_theme("codex_logo.png").'" width="135" height="33" hspace="10" alt="<?php print $GLOBALS['sys_default_domain']; ?>"  border="0"></A><br>
        </noscript>
	<!-- end OSDN navdropdown -->
<?php
}

/*
	Picks random OSDN sites to display
*/
function osdn_print_randpick($sitear, $num_sites = 1) {
	shuffle($sitear);
	reset($sitear);
        while ( ( $i < $num_sites ) && (list($key,$val) = each($sitear)) ) {
		list($key,$val) = each($val);
		print "\t\t&nbsp;&middot;&nbsp;<a href='$val' class='osdntext'>$key</a>\n";
		$i++;
	}
	print '&nbsp;&middot;&nbsp;';
}

function osdn_print_navbar() {
    print '
           <!-- OSDN navbar -->
           <table width="100%" cellpadding="2" cellspacing="0" border="0">
           <tr> 
	    <td valign="middle" align="left">
';

    $motd = getenv('SF_LOCAL_INC_PREFIX').'/etc/motd.inc';
    if (file_exists($motd) && filesize($motd)>0 ) {
	$fp = fopen($motd,"r");
	$output = fread($fp,200000);
	echo $output;
	fclose($fp);
    } else {
	print '<span class="osdn">Network Gallery&nbsp;:&nbsp;';
	osdn_print_randpick($GLOBALS['osdn_sites'], 5);
	print '</span>';
    }

// LJ
print '	     </td>';

	srand((double)microtime()*1000000);
        $random_num=rand(0,100000);

	if (session_issecure()) {
		$_SSL='s';
	} else {
		$_SSL='';
	}
	print '
	</tr>
	<!--/tbody--> 
</table>

<!-- End OSDN NavBar -->
';
}
?>
