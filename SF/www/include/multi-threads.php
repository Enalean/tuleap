<?php
//////////////////////////////////////////////////////////////////////////////////
//										//
//	 Copyright (C) 1998	Phorum Development Team				//
//	 http://www.phorum.org							//
//										//
//	 This program is free software. You can redistribute it and/or modify	//
//	 it under the terms of the GNU General Public License Version 2 as	//
//	 published by the Free Software Foundation.				//
//										//
//	 This program is distributed in the hope that it will be useful,	//
//	 but WITHOUT ANY WARRANTY, without even the implied warranty of		//
//	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the		//
//	 GNU General Public License for more details.			 	//
//										//
//	 You should have received a copy of the GNU General Public License	//
//	 along with this program; if not, write to the Free Software		//
//	 Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.		//
//										//
//////////////////////////////////////////////////////////////////////////////////

 
	$t_gif='<IMG SRC="/images/phorum/t.gif" WIDTH=12 HEIGHT=21 BORDER=0>';
	$l_gif='<IMG SRC="/images/phorum/l.gif" WIDTH=12 HEIGHT=21 BORDER=0>';
	$p_gif='<IMG SRC="/images/phorum/p.gif" WIDTH=9 HEIGHT=21 BORDER=0>';
	$m_gif='<IMG SRC="/images/phorum/m.gif" WIDTH=9 HEIGHT=21 BORDER=0>';
	$c_gif='<IMG SRC="/images/phorum/c.gif" WIDTH=9 HEIGHT=21 BORDER=0>';
	$i_gif='<IMG SRC="/images/phorum/i.gif" WIDTH=12 HEIGHT=21 BORDER=0>';
	$n_gif='<IMG SRC="/images/phorum/n.gif" WIDTH=9 HEIGHT=21 BORDER=0>';
	$space_gif='<IMG SRC="/images/phorum/trans.gif" WIDTH=5 HEIGHT=21 BORDER=0>';
	$trans_gif='<IMG SRC="/images/phorum/trans.gif" WIDTH=12 HEIGHT=21 BORDER=0>';
	$TableWidth='100%';
	$TableHeaderColor='#003399';
	$TableHeaderFontColor='#FFFFFF';
	$TableBodyColor1='#FFFFFF';
	$TableBodyFontColor1='#000000';
	$TableBodyColor2='#EEEEF8';
	$TableBodyFontColor2='#000000';
	$NavColor='#FFFFFF';   
	$NavFontColor='#000000'; 

	function echo_data($image, $topic, $row_color) {
		GLOBAL $TableWidth,$TableHeaderColor,$TableHeaderFontColor;
		GLOBAL $TableBodyColor1,$TableBodyFontColor1,$TableBodyColor2,$TableBodyFontColor2;
		GLOBAL $read_page,$ext,$collapse,$id,$UseCookies;
		GLOBAL $space_gif,$num,$old_message,$haveread;
		$thread_total="";
		GLOBAL $lNew, $GetVars;

		//alternate bgcolor
		if (($row_color%2)==0) {
			$bgcolor=$TableBodyColor1;
			$font_color=$TableBodyFontColor1;
		} else{
			$bgcolor=$TableBodyColor2;
			$font_color=$TableBodyFontColor2;
		}	

		$subject ="<TABLE CELLSPACING=0 CELLPADDING=0 BORDER=0";
		if ($bgcolor!="") {
				$subject.=" BGCOLOR=\"".$bgcolor."\"";
		}
		$subject.=">\n";
		$subject.="<TR>\n<TD>";
		$subject.=$space_gif;
		$subject.=$image."</TD>\n<TD><FONT COLOR=\"$font_color\">&nbsp;";

/*		if ($id==$topic["id"] && $read=true) {
			$subject .= "<b>".$topic["subject"]."</b>";
			$author = "<b>".$topic["author"]."</b>";
			$datestamp = "<b>".date_format($topic["datestamp"])."</b>";
		} else {
*/
			$subject.="<a href=\"$read_page.$ext?num=$num&id=".$topic["id"];
			if ($topic["id"]==$topic["thread"]) {
				$subject.="&loc=0";
			}
			$subject.="&thread=".$topic["thread"]."$GetVars\">".$topic["subject"]."</a>";
			$author = $topic["author"];
			$datestamp = date_format($topic["datestamp"]);
//		}

		$subject.="&nbsp;&nbsp;</font>";
		if (isset($haveread[0])) {
			$temp=$haveread[0];
		} else {
			$temp=0;
		}
/*
		if ($temp<$topic["id"] && !IsSet($haveread[$topic["id"]]) && $UseCookies) {
			$subject.="<font face=\"MS Sans Serif,Geneva\" size=\"-2\" color=\"#FF0000\">".$lNew."</font>";
		}
*/
		$subject.="</TD>\n</TR>\n</TABLE>";
		?>	
		<TR VALIGN=middle>
		<TD<?PHP echo bgcolor($bgcolor);?>><?php echo $subject;?></TD>
		<TD<?PHP echo bgcolor($bgcolor);?> nowrap><FONT COLOR="<?php echo $font_color;?>"><?php echo $author;?></FONT></TD>
		<TD<?PHP echo bgcolor($bgcolor);?> nowrap><FONT SIZE="-2" COLOR="<?php echo $font_color;?>"><?php echo $datestamp;?>&nbsp;</FONT></TD>
		</TR>
		<?php
	}

	function thread($seed=0){

		GLOBAL $row_color_cnt;
		GLOBAL $messages,$threadtotal;
		GLOBAL $font_color, $bgcolor;
		GLOBAL $t_gif,$l_gif,$p_gif,$m_gif,$c_gif,$i_gif,$n_gif,$trans_gif;		
		
		$image="";
		$images="";
			
		if(!IsSet($row_color_cnt)){
			$row_color_cnt=0;
		}

		$row_color_cnt++;

		//for submessages only
		if ($seed!="0") {
			$parent=$messages[$seed]["parent"];
			if($parent!=0){
				if(!IsSet($messages[$parent]["images"])){	
					$messages[$parent]["images"]="";
				}
				$image=$messages[$parent]["images"];
				if($messages[$parent]["max"]==$messages[$seed]["id"]){
					$image.=$l_gif;
				}
				else{
					$image.=$t_gif;
				}
			}


			if(isset($messages[$seed]["replies"])){
				if(IsSet($messages[$parent]["images"])){
					$messages[$seed]["images"]=$messages[$parent]["images"];
					if($seed==$messages["$parent"]["max"]){
						$messages[$seed]["images"].=$trans_gif;
					}
					else{
						$messages[$seed]["images"].=$i_gif;
					} 
				}
				$image.=$m_gif;
			} else {
				//this is not the top message in the thread
				if ($messages[$seed]["parent"]!=0) {
					$image.=$c_gif;
				} else {
					//top of the thread
					if ($threadtotal[$messages[$seed]["thread"]]>1) {
						//submessage and more than one message in the thread
						$image.=$p_gif;
					} else {
						//submessage and only message in the thread
						$image.=$n_gif;
					}
				}
			}				

			echo_data($image, $messages[$seed], $row_color_cnt);
		}//end of: if($seed!="0")
		
		if (isset($messages[$seed]["replies"])) {
			//count the followups to this message
			$count=count($messages[$seed]["replies"]);

			for($x=1;$x<=$count;$x++){
				//iterate and show the followups to this message
				$key=key($messages[$seed]["replies"]);
				thread($key);
				next($messages[$seed]["replies"]);
			} 
		}
	}	

	//get the first row out of the db result set
	$row=$msg_list->firstrow();

	//see if it was successful
	if (is_array($row)) {
		if (!$read) {
			//if showing more than one thread, as on the master project page
			$rec=$thread_list->firstrow();
			while (is_array($rec)) {
				$thd=$rec["thread"];
				if (!isset($rec["tcount"])) {
					$rec["tcount"]=0;
				}
				$tcount=$rec["tcount"];
				$threadtotal[$thd]=$tcount;
				$rec=$thread_list->getrow();
			}
		} else {
			//showing only one thread
			$threadtotal[$thread]=$msg_list->numrows();
		}
		$topics["max"]="0";
		$topics["min"]="0";

		while (is_array($row)) {
			//iterate and build an associative array of messages for this thread
			$x="".$row["id"]."";
			$p="".$row["parent"]."";
			$messages["$x"]=$row;
			$messages["$p"]["replies"]["$x"]="$x";
			$messages["$p"]["max"]=$row["id"];
			if(!isset($messages["max"])) {
				$messages["max"]=0;
			}
			if(!isset($messages["min"])) {
				$messages["min"]=0;
			}
			if($messages["max"]<$row["thread"]) {
				$messages["max"]=$row["thread"];
			}
			if($messages["min"]>$row["thread"]) {
				$messages["min"]=$row["thread"];
			}
			$row=$msg_list->getrow();
		}
	}
	
?>
<TABLE WIDTH="<?php echo $TableWidth;?>" CELLSPACING=0 CELLPADDING=0 BORDER=0>
<TR>
		<TD HEIGHT=21<?PHP echo bgcolor($TableHeaderColor);?>><FONT COLOR="<?PHP echo $TableHeaderFontColor; ?>">&nbsp;<?php echo $lTopics;?></FONT></TD>
		<TD HEIGHT=21<?PHP echo bgcolor($TableHeaderColor);?> NOWRAP WIDTH=150><FONT COLOR="<?php echo $TableHeaderFontColor; ?>"><?php echo $lAuthor;?>&nbsp;</FONT></TD>
		<TD HEIGHT=21<?PHP echo bgcolor($TableHeaderColor);?> NOWRAP WIDTH=100><FONT COLOR="<?php echo $TableHeaderFontColor; ?>"><?php echo $lDate;?></FONT></TD>
</TR>
<?php
	thread();
?>
</TABLE>
