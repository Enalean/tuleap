<?php

  //$Language->loadLanguageMsg('include/include');

include(util_get_content('layout/osdn_sites'));

function osdn_nav_dropdown() {
  GLOBAL $osdn_sites, $Language;

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

        <a href="<?php echo get_server_url();?>"><?php echo html_image("codex_logo.png",array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0")); ?></A><br>
        <select name=navbar onChange="handle_navbar(selectedIndex,this)">
																									    <option><?php echo $Language->getText('include_osdn','network_gallery'); ?></option>
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
        <a href="<?php echo get_server_url();?>"><?php echo html_image("codex_logo.png", array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0")); ?></A><br>
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
  global $Language;
    print '
           <!-- OSDN navbar -->
           <table width="100%" cellpadding="2" cellspacing="0" border="0">
           <tr> 
	    <td valign="middle" align="left">
';

    $motd = util_get_content('others/motd');
    if (!strpos($motd,"empty.txt")) { # empty.txt returned when no motd file found
        include($motd);
    } else {
	print '<span class="osdn">'.$Language->getText('include_osdn','network_gallery').'&nbsp;:&nbsp;';
	osdn_print_randpick($GLOBALS['osdn_sites'], 5);
	print '</span>';
    }

print '	     </td>';

	srand((double)microtime()*1000000);
        $random_num=rand(0,10000);

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
