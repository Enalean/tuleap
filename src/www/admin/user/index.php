<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Arnaud Salvucci, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/admin/UserControler.class.php');
//require_once('www/admin/user/UserAutocompletionForm.class.php');

$controler = new UserControler();
$controler->process();

?>

<script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
<script type="text/javascript" src="/scripts/autocompletion.js"></script>

<script type="text/javascript">

    //    if(document.getElementById('user_name_search').value != '') {





autocomplete();


//    }




</script>