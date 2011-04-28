<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                version='1.0'>

<!--
//
// Copyright (c) Xerox Corporation, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING. 
//
// $Id: 
//
//	Originally written by Stephane Bouhet 2002, Codendi Team, Xerox
//
-->

<!-- CX-SBT: For example with programlisting, add a border with a grey background -->
<xsl:template match="example/programlisting">
    <!-- CX-SBT: Add a table for having a border with a grey background -->
    <table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000" bgcolor="#EEEEEE">
      <tr>
        <td>
        <pre class="programlisting">
    <xsl:apply-templates/>
        </pre>
        </td>
      </tr>
    </table>
</xsl:template>

</xsl:stylesheet>
