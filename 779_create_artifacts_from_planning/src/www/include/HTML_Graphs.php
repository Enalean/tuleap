<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*
#######################################################################
#
#       
#
#   $Author: root $
#   $Locker$
#
#     $Date: 2001-03-06 11:03:47 +0100 (Tue, 06 Mar 2001) $
#
#   $Source$
# $Revision: 2 $
#    $State$
#
#      Revision 1.5  1998/11/05 06:15:52  pdavis
#      Added error_reporting setting per Jean-Pierre Arneodo's request.
#      (Though redundant) Added html_graph_init() to initialize vars array.
#
#      Revision 1.4  1998/07/08 05:24:25  pdavis
#      Add double_vertical_graph from Jan Diepens.
#      Added "max" function to find $largest in examples page.
#      Added code to increase values of zero to one.
#      Added double_vertical_graph example
#      Combined all source into one zip.
#
#      Revision 1.3  1998/06/17 23:37:19  pdavis
#      Added mixed color codes and images to double graph.
#
#      Revision 1.2  1998/06/17 21:20:20  pdavis
#      Fixed Background problem, added mixed color codes and graphics.
#
#      Revision 1.1  1998/06/17 15:52:41  pdavis
#      Initial revision
#
#
#######################################################################
#
#     *
#     *  Phil Davis
#     *
#     *  Smyrna, Tennessee  37167  USA
#     *
#     *  pdavis@pobox.com
#     *  http://www.pobox.com/~pdavis/
#     *
#
#     (C) Copyright 1998 
#         Phil Davis
#         Printed in the United States of America
#
#     This program is free software; you can redistribute it
#     and/or modify it under the terms of the GNU General
#     Public License version 2 as published by the Free
#     Software Foundation.
#
#     This program is distributed in the hope that it will
#     be useful, but WITHOUT ANY WARRANTY; without even the
#     implied warranty of MERCHANTABILITY or FITNESS FOR A
#     PARTICULAR PURPOSE.  See the GNU General Public License
#     for more details.
#
#     Released under GNU Public License v2.0, available
#     at www.fsf.org.  The author hereby disclaims all
#     warranties relating to this software, express or implied,
#     including with no limitation any implied warranties of
#     merchantability, quality performance, or fitness for a
#     particular purpose. The author and their distributors
#     shall not be liable for any special, incidental,
#     consequential, indirect or similar damages due to loss
#     of data, even if an agent of the author has been found
#     to be the source of loss or damage. In no event shall the
#     author's liability for any damages ever exceed the price
#     paid for the license to use software, regardless of the
#     form of the claim. The person using the software bears all
#     risk as to the quality and performance of the software.
#
#     Swim at your own risk!
#
#     This software program, documentation, accompanying
#     written and disk-based notes and specifications, and all
#     referenced and related program files, screen display
#     renditions, and text files, are the property of the
#     author.
#
#     The authors have done their best to insure that the
#     material found in this document is both useful and
#     accurate. However, please be aware that errors may exist,
#     the author does not make any guarantee concerning the
#     accuracy of the information found here or in the uses
#     to which it may be put.
#
#######################################################################
#
#  About:
#
#  The following PHP3 code provides a nice class interface for
#  html graphs.  It provides a single, reasonably consistent
#  interface for creating HTML based graphs.  The idea behind
#  this code is that the user of the class sets up four or five
#  arrays and pass these to html_graph() which then takes
#  care of all the messy HTML layout.  I am reasonably happy
#  with the outcome of this interface.  The HTML that must be
#  generated for HTML graphs *is* messy, and the interface is
#  very clean and flexible.  I think that once you generate
#  one graph with it, you'll never look at creating HTML graphs
#  the same.  The arrays that must be set up consist of:
#
#       * A names array containing column/row identifiers ($names)
#       * One or two values arrays containg corresponding 
#         values to the column/row names ($values & $dvalues)
#       * One or two bars array which also corresponds to the names
#         array.  The values in these arrays are URLS to graphics
#         or color codes starting with a # which will be used to
#         generate the graph bar.  Color codes and graphics may
#         be mixed in the same chart, although color codes can't 
#         be used on Vertical charts. ($bars & $dbars)
#       * The heart of customization... a vals array.  If this 
#         array isn't created then html_graphs will use all 
#         default values for the chart.  Items that are customizable
#         include font styles & colors, backgrounds, graphics, 
#         labels, cellspacing, cellpadding, borders, anotations
#         and scaling factor. ($vals)
#
#######################################################################
#
#  Known Bugs:
# 
#  * Currently the $vals["background"] tag element doesn't 
#    work in Netscape.
#
#######################################################################
# 
#  To Do: 
# 
#  * Would like to make the $vals array to html_graph() completely
#    optional.  Currently it has to at least be an empty array.
#
#######################################################################
#
# Contributors:
#
#  Jan Diepens - Eindhoven University of Technologie
#  Jean-Pierre Arneodo
#
#######################################################################
#
# Contact:
#
# If you have questions, suggestions, bugs, bug fixes, or enhancements 
# please send them to pdavis@pobox.com so that they may be wrapped into 
# future versions of HTML_Graph.
#
#######################################################################
#
#  Examples:
#
#  See http://www.pobox.com/~pdavis/programs/
#
#######################################################################
*/

/*
#######################################################################
#
#  Function:  html_graph($names, $values, $bars, $vals[, $dvalues, $dbars]) 
#
#   Purpose:  Calls routines to initialize defaults, set up table
#             print data, and close table.
#
# Arguments: 
#                   $names - Array of element names.
#                  $values - Array of corresponding values for elements.
#                    $bars - Array of corresponding graphic image names 
#                            or color codes (begining with a #) for elements.
#                            Color codes can't be used on vertical charts.
#                 $dvalues - Array of corresponding values for elements.
#                            This set is required only in the double graph.
#                   $dbars - Array of corresponding graphic image names 
#                            or color codes (begining with a #) for elements.
#                            This set is required only in the double graph.
#
#                    $vals -  array("vlabel"=>"",
#                                   "hlabel"=>"",
#                                   "type"=>"",
#                                   "cellpadding"=>"",
#                                   "cellspacing"=>"",
#                                   "border"=>"",
#                                   "width"=>"",
#                                   "background"=>"",
#                                   "vfcolor"=>"",
#                                   "hfcolor"=>"",
#                                   "vbgcolor"=>"",
#                                   "hbgcolor"=>"",
#                                   "vfstyle"=>"",
#                                   "hfstyle"=>"",
#                                   "noshowvals"=>"",
#                                   "scale"=>"",
#                                   "namebgcolor"=>"",
#                                   "valuebgcolor"=>"",
#                                   "namefcolor"=>"",
#                                   "valuefcolor"=>"",
#                                   "namefstyle"=>"",
#                                   "valuefstyle"=>"",
#                                   "doublefcolor"=>"")
#
#             Where:
#
#                   vlabel - Vertical Label to apply
#                            default is NUL
#                   hlabel - Horizontal Label to apply
#                            default is NUL
#                     type - Type of graph 
#                            0 = horizontal
#                            1 = vertical
#                            2 = double horizontal
#                            3 = double vertical 
#                            default is 0
#              cellpadding - Padding for the overall table
#                            default is 0
#              cellspacing - Space for the overall table
#                            default is 0
#                   border - Border size for the overall table
#                            default is 0
#                    width - Width of the overall table
#                            default is NUL
#               background - Background image for the overall table
#                            If this value exists then no BGCOLOR
#                            codes will be added to table elements.
#                            default is NUL
#                  vfcolor - Vertical label font color
#                            default is #000000
#                  hfcolor - Horizontal label font color
#                            default is #000000
#                 vbgcolor - Vertical label background color
#                            Not used if background is set
#                            default is #FFFFFF
#                 hbgcolor - Horizontal label background color
#                            Not used if background is set
#                            default is #FFFFFF
#                  vfstyle - Vertical label font style
#                            default is NUL 
#                  hfstyle - Horizontal label font style
#                            default is NUL 
#               noshowvals - Don't show numeric value at end of graphic
#                            Boolean value, default is FALSE
#                    scale - Scale values by some number.
#                            default is 1.
#              namebgcolor - Color code for element name cells
#                            Not used if background is set
#                            default is "#000000"
#             valuebgcolor - Color code for value cells
#                            Not used if background is set
#                            default is "#000000"
#               namefcolor - Color code for font of name element
#                            default is "#FFFFFF"
#              valuefcolor - Color code for font of value element
#                            default is "#000000"
#               namefstyle - Style code for font of name element
#                            default is NUL 
#              valuefstyle - Style code for font of value element
#                            default is NUL 
#             doublefcolor - Color code for font of second element value
#                            default is "#886666"
#
#######################################################################
*/
function html_graph($names, $values, $bars, $vals, $dvalues=0, $dbars=0) 
   {
    // Set the error level on entry and exit so as not to interfear
    // with anyone elses error checking.
    $er = error_reporting(1);

    // Set the values that the user didn't
    $vals = hv_graph_defaults($vals);
    start_graph($vals, $names);

    if ($vals["type"] == 0)
       {
        horizontal_graph($names, $values, $bars, $vals);
       }
    elseif ($vals["type"] == 1)
       {
        vertical_graph($names, $values, $bars, $vals);
       }
    elseif ($vals["type"] == 2)
       {
        double_horizontal_graph($names, $values, $bars, $vals, $dvalues, $dbars);
       }
    elseif ($vals["type"] == 3)
       {
        double_vertical_graph($names, $values, $bars, $vals, $dvalues, $dbars);
       }

    end_graph();

    // Set the error level back to where it was.
    error_reporting($er);  
   }

/*
####################################################################### 
#
#  Function:  html_graph_init()
#
#   Purpose:  Sets up the $vals array by initializing all values to 
#             null.  Used to avoid warnings from error_reporting being
#             set high.  This routine only needs to be called if you 
#             are woried about using uninitialized variables.
#           
#   Returns:  The initialized $vals array
# 
####################################################################### 
*/
function html_graph_init()
   {
    $vals = array("vlabel"=>"",
                  "hlabel"=>"",
                  "type"=>"",
                  "cellpadding"=>"",
                  "cellspacing"=>"",
                  "border"=>"",
                  "width"=>"",
                  "background"=>"",
                  "vfcolor"=>"",
                  "hfcolor"=>"",
                  "vbgcolor"=>"",
                  "hbgcolor"=>"",
                  "vfstyle"=>"",
                  "hfstyle"=>"",
                  "noshowvals"=>"",
                  "scale"=>"",
                  "namebgcolor"=>"",
                  "valuebgcolor"=>"",
                  "namefcolor"=>"",
                  "valuefcolor"=>"",
                  "namefstyle"=>"",
                  "valuefstyle"=>"",
                  "doublefcolor"=>"");

    return($vals);
   }
/*
####################################################################### 
#
#  Function:  start_graph($vals, $names)
#
#   Purpose:  Prints out the table header and graph labels.
#
####################################################################### 
*/
function start_graph($vals, $names)
   {
    print "<!-- Start Inner Graph Table -->\n\n<TABLE";
    print ' CELLPADDING="' . $vals["cellpadding"] . '"';
    print ' CELLSPACING="' . $vals["cellspacing"] . '"';
    print ' BORDER="' . $vals["border"] . '"';

    if ($vals["width"] != 0) { print ' WIDTH="' . $vals["width"] . '"'; }
    if ($vals["background"]) { print ' BACKGROUND="' . $vals["background"] . '"'; }

    print '>';

    if (($vals["vlabel"]) || ($vals["hlabel"]))
       {
        if (($vals["type"] == 0) || ($vals["type"] == 2 ))// horizontal chart
           { 
            $rowspan = SizeOf($names) + 1; 
            $colspan = 3; 
           }
        elseif ($vals["type"] == 1 || ($vals["type"] == 3 )) // vertical chart
           {
            $rowspan = 3;
            $colspan = SizeOf($names) + 1; 
           }

        print '<TR><TD ALIGN=CENTER VALIGN="CENTER" ';

        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print 'BGCOLOR="' . $vals["hbgcolor"] . '"'; }

        print ' COLSPAN="' . $colspan . '">';
        print '<FONT COLOR="' . $vals["hfcolor"] . '" STYLE="' . $vals["hfstyle"] . '">';
        print "<B>" . $vals["hlabel"] . "</B>";
        print '</FONT></TD></TR>';

        print '<TR><TD ALIGN="CENTER" VALIGN="CENTER" ';

        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print 'BGCOLOR="' . $vals["vbgcolor"] . '"'; }

        print ' ROWSPAN="' . $rowspan . '">';
        print '<FONT COLOR="' . $vals["vfcolor"] . '" STYLE="' . $vals["vfstyle"] . '">';
        print "<B>" . $vals["vlabel"] . "</B>";
        print '</FONT></TD>';
       }
   }

/*
####################################################################### 
#
#  Function:  end_graph()
#
#   Purpose:  Prints out the table footer.
#
####################################################################### 
*/
function end_graph()
   {
    print "\n</TABLE>\n\n<!-- end inner graph table -->\n\n";
   }

/*
####################################################################### 
#
#  Function:  hv_graph_defaults($vals)
#
#   Purpose:  Sets the default values for the $vals array 
#
####################################################################### 
*/
function hv_graph_defaults($vals)
   {
    if (! $vals["vfcolor"]) { $vals["vfcolor"]="#000000"; }
    if (! $vals["hfcolor"]) { $vals["hfcolor"]="#000000"; }
    if (! $vals["vbgcolor"]) { $vals["vbgcolor"]="#FFFFFF"; }
    if (! $vals["hbgcolor"]) { $vals["hbgcolor"]="#FFFFFF"; }
    if (! $vals["cellpadding"]) { $vals["cellpadding"]=0; }
    if (! $vals["cellspacing"]) { $vals["cellspacing"]=0; }
    if (! $vals["border"]) { $vals["border"]=0; }
    if (! $vals["scale"]) { $vals["scale"]=1; }
    if (! $vals["namebgcolor"]) { $vals["namebgcolor"]="#FFFFFF"; }
    if (! $vals["valuebgcolor"]) { $vals["valuebgcolor"]="#FFFFFF"; }
    if (! $vals["namefcolor"]) { $vals["namefcolor"]="#000000"; }
    if (! $vals["valuefcolor"]) { $vals["valuefcolor"]="#000000"; }
    if (! $vals["doublefcolor"]) { $vals["doublefcolor"]="#886666"; }

    return ($vals);
   }

/*
####################################################################### 
#
#  Function:  horizontal_graph($names, $values, $bars, $vals) 
#
#   Purpose:  Prints out the actual data for the horizontal chart. 
#
####################################################################### 
*/
function horizontal_graph($names, $values, $bars, $vals) 
   {
    for( $i=0;$i<SizeOf($values);$i++ )
       { 
?>

	<TR>
	<TD ALIGN="RIGHT" <?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["namebgcolor"] . '"'; }
?>>
		<FONT SIZE="-1" COLOR="<?php 
			echo $vals["namefcolor"]; 
		?>" STYLE="<?php 
			echo $vals["namefstyle"]; 
	echo "\">";
        echo "\n".$names[$i]; ?>
		</FONT>
	</TD>

	<TD  ALIGN="LEFT" <?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }

	echo ">";

        // Decide if the value in bar is a color code or image.
        if (ereg("^#", $bars[$i]))
           { 
?>

		<TABLE ALIGN="LEFT" CELLPADDING=0 CELLSPACING=0  BGCOLOR="<?php echo $bars[$i] ?>" WIDTH="<?php echo $values[$i] * $vals["scale"] ?>">
			<TR><TD>&nbsp;</TD></TR>
		</TABLE>

<?php
            }
         else
            {
             print '<IMG SRC="' . $bars[$i] . '"';
             print ' HEIGHT=10 WIDTH="' . $values[$i] * $vals["scale"] . '">';
            }
        if (! $vals["noshowvals"])
           {
            print '		<I><FONT SIZE="-2" COLOR="' . $vals["valuefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $values[$i] . ")</FONT></I>";
           }
?>

	</TD> 
	</TR>
<?php
       } // endfor

   } // end horizontal_graph

/*
####################################################################### 
#
#  Function:  vertical_graph($names, $values, $bars, $vals) 
#
#   Purpose:  Prints out the actual data for the vertical chart. 
#
####################################################################### 
*/
function vertical_graph($names, $values, $bars, $vals) 
   {
    print "<TR>";

    for( $i=0;$i<SizeOf($values);$i++ )
       { 

        print '<TD  ALIGN="CENTER" VALIGN="BOTTOM" ';

        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }
        print ">";

        if (! $vals["noshowvals"])
           {
            print '<I><FONT SIZE="-2" COLOR="' . $vals["valuefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $values[$i] . ")</FONT></I><BR>";
           }
?>

         <IMG SRC="<?php echo $bars[$i] ?>" WIDTH=5 HEIGHT="<?php 

        // Values of zero are displayed wrong because a image height of zero 
        // gives a strange behavior in Netscape. For this reason the height 
        // is set at 1 pixel if the value is zero. - Jan Diepens
        if ($values[$i] != 0)
           {
            echo $values[$i] * $vals["scale"];
           } 
        else 
           { 
            echo "1";
           } 
?>">

         </TD> 
<?php
       } // endfor

    print "</TR><TR>";

    for( $i=0;$i<SizeOf($values);$i++ )
       { 
?>
        <TD ALIGN="CENTER" VALIGN="TOP" 

<?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["namebgcolor"] . '"'; }
?>
         >
         <FONT SIZE="-1" COLOR="<?php echo $vals["namefcolor"] ?>" STYLE="<?php echo $vals["namefstyle"] ?>">
         <?php echo $names[$i] ?>
         </FONT>
        </TD>
<?php
       } // endfor

   } // end vertical_graph 

/*
####################################################################### 
#
#  Function:  double_horizontal_graph($names, $values, $bars, 
#                                     $vals, $dvalues, $dbars) 
#
#   Purpose:  Prints out the actual data for the double horizontal chart. 
#
####################################################################### 
*/
function double_horizontal_graph($names, $values, $bars, $vals, $dvalues, $dbars) 
   {
    for( $i=0;$i<SizeOf($values);$i++ )
       { 
?>
       <TR>
        <TD ALIGN=RIGHT 
<?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["namebgcolor"] . '"'; }
?>
         >
         <FONT SIZE="-1" COLOR="<?php echo $vals["namefcolor"] ?>" STYLE="<?php echo $vals["namefstyle"] ?>">
         <?php echo $names[$i] ?>
         </FONT>
        </TD>
        <TD  ALIGN=LEFT 
<?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }
?>
         >
         <TABLE ALIGN="LEFT" CELLPADDING=0 CELLSPACING=0 WIDTH="<?php echo $dvalues[$i] * $vals["scale"] ?>">
          <TR><TD 
<?php
        // Set background to a color if it starts with # or
        // an image otherwise.
        if (ereg("^#", $dbars[$i])) { print 'BGCOLOR="' . $dbars[$i] . '">'; }
        else { print 'BACKGROUND="' . $dbars[$i] . '">'; }
?>
           <NOWRAP>
<?php
        // Decide if the value in bar is a color code or image.
        if (ereg("^#", $bars[$i]))
           { 
?>
            <TABLE ALIGN="LEFT" CELLPADDING=0 CELLSPACING=0 
             BGCOLOR="<?php echo $bars[$i] ?>" 
             WIDTH="<?php echo $values[$i] * $vals["scale"] ?>">
             <TR><TD>&nbsp</TD></TR>
            </TABLE>
<?php
            }
         else
            {
             print '<IMG SRC="' . $bars[$i] . '"';
             print ' HEIGHT=10 WIDTH="' . $values[$i] * $vals["scale"] . '">';
            }          

        if (! $vals["noshowvals"])
           {
            print '<I><FONT SIZE="-3" COLOR="' . $vals["valuefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $values[$i] . ")</FONT></I>";
           }
?>
           </NOWRAP>
          </TD></TR>
         </TABLE>
<?php
        if (! $vals["noshowvals"])
           {
            print '<I><FONT SIZE="-3" COLOR="' . $vals["doublefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $dvalues[$i] . ")</FONT></I>";
           }
?>
        </TD> 
       </TR>
<?php
       } // endfor

   } // end double_horizontal_graph

/*
####################################################################### 
#
#  Function:  double_vertical_graph($names, $values, $bars, $vals, $dvalues, $dbars) 
#
#   Purpose:  Prints out the actual data for the double vertical chart. 
#
#    Author: Jan Diepens
#
####################################################################### 
*/
function double_vertical_graph($names, $values, $bars, $vals, $dvalues, $dbars) 
   {
   // print "<TR>";

    for( $i=0;$i<SizeOf($values);$i++ )
       { 

        print '<TD  ALIGN="CENTER" VALIGN="BOTTOM" ';
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }
        print ">";

	print '<TABLE><TR><TD ALIGN="CENTER" VALIGN="BOTTOM" ';

        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }
        print ">";

        if (! $vals["noshowvals"])
           {
            print '<I><FONT SIZE="-2" COLOR="' . $vals["valuefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $values[$i] . ")</FONT></I><BR>";
           }
?>

         <IMG SRC="<?php echo $bars[$i] ?>" WIDTH=10 HEIGHT="<?php if ($values[$i]!=0){
		echo $values[$i] * $vals["scale"];
		} else { echo "1";} ?>">
         </TD><TD ALIGN="CENTER" VALIGN="BOTTOM"
<?php
         // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["valuebgcolor"] . '"'; }
        print ">";

        if (! $vals["noshowvals"])
           {
            print '<I><FONT SIZE="-2" COLOR="' . $vals["doublefcolor"] . '" ';
            print ' STYLE="' . $vals["valuefstyle"] . '">('; 
            print $dvalues[$i] . ")</FONT></I><BR>";
           }
?>

         <IMG SRC="<?php echo $dbars[$i] ?>" WIDTH=10 HEIGHT="<?php if ($dvalues[$i]!=0){
		echo $dvalues[$i] * $vals["scale"];
		} else { echo "1";} ?>">
         </TD></TR></TABLE>
	 </TD>
<?php
       } // endfor

    print "</TR><TR>";

    for( $i=0;$i<SizeOf($values);$i++ )
       { 
?>
        <TD ALIGN="CENTER" VALIGN="TOP" 

<?php
        // If a background was choosen don't print cell BGCOLOR
        if (! $vals["background"]) { print ' BGCOLOR="' . $vals["namebgcolor"] . '"'; }
?>
         >
         <FONT SIZE="-1" COLOR="<?php echo $vals["namefcolor"] ?>" STYLE="<?php echo $vals["namefstyle"] ?>">
         <?php echo $names[$i] ?>
         </FONT>
        </TD>
<?php
       } // endfor

   } // end double_vertical_graph
?>
