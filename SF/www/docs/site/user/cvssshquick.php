<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"CVS/SSH SourceForge Documentation"));
?>

<B>Documentation CVS de SourceForge</B>

<P>Cette documentation est vraiment limitée pour le moment, mais elle va d&eacute;ja vous permettre de d&eacute;marrer.
<P>Pour tout les acc&egrave;s d&eacute;veloppeurs (lecture/ecriture), vous devrez utiliser SSH.
Un client SSH (1.x) doit etre disponible sur votre machine locale. La variable d'environnement CVS_RSH doit contenir le chemin de ssh.
Ceci peut etre r&eacute;alis&eacute; sur la plupart des syst&egrave;mes Linux (bash) en tapant :
<UL><B><I>export CVS_RSH=ssh</I></B>
</UL>

<P>L'acc&egrave;s anonyme &agrave; CVS utilise le pserver CVS, et ne requiert pas SSH.

<P>Si vous obtenez des erreurs du type 'permission denied' sans qu'on vous demande de saisir un mot de passe,
la variable ne doit pas etre correctement initialis&eacute;e, ou SSH n'est pas disponible sur votre syst&egrave;me.
Arrangez ceci, avant de suspecter un probl&egrave;me de mot de passe.

<P><B>Comment importer du code source dans votre d&eacute;p&ocirc;t (repository)</B>
<UL>
<LI>Sur votre machine locale, allez dans le r&eacute;pertoire dans lequel se situent les fichiers (et sous r&eacute;pertoires) que vous
voulez importer.
Tout les fichiers qui se situent actuellement dans ce r&eacute;pertoire, ainsi que les sous r&eacute;pertoires, vont &ecirc;tre import&eacute;s dans l'arbre CVS.
<LI>Tapez ce qui suit o&ugrave; nomutilisateur est votre nom d'utilisateur SourceForge,
votreprojet est le nom unix de votre projet, et
nomrepertoire est le nom du nouveau repertoire racine pour CVS (ex : . pour le repertoire dans lequel vous vous situez.)
<BR><B><I>cvs -dnomutilisateur@cvs.votreprojet.sourceforge.net:/cvsroot/votreprojet import nomrepertoire constructeur debut</I></B>
</UL>

<P><B>Comment r&eacute;cup&eacute;rer les sources via SSH</B>
<UL>
<LI>Tapez ce qui suit, en effectuant les modifications n&eacute;cessaires pour votre nom d'utilisateur, et votre projet.
<BR><B><I>cvs -dnomutilisateur@cvs.votreprojet.sourceforge.net:/cvsroot/votreprojet co nomrepertoire</I></B>
<LI>Apr&egrave;s la r&eacute;cup&eacute;ration intiale des fichiers sources, vous pouvez aller dans ce r&eacute;pertoire et &eacute;xecuter
les commandes cvs, sans le marqueur -d. Par exemple :
<BR><B><I>cvs update<BR>cvs commit -m "commentaires pour cette modification"<BR>cvs add monfichier.c</I></B>
</UL>

<P><B>Comment r&eacute;cup&eacute;rer les sources de mani&egrave;re anonyme en passant par le pserver.</B>
<UL>
<LI>Tapez ce qui suit, en effectuant les modifications n&eacute;cessaires pour votre nom d'utilisateur, et votre projet.
<BR><B><I>cvs -d:pserver:anonymous@cvs.votreprojet.sourceforge.net:/cvsroot/votreprojet login</I></B>
<LI>Après vous &ecirc;tre connect&eacute; de mani&egrave;re anonyme :
<BR><B><I>cvs -d:pserver:anonymous@cvs.votreprojet.sourceforge.net:/cvsroot/votreprojet co nomrepertoire</I></B>
<LI>Apr&egrave;s la r&eacute;cup&eacute;ration intiale des fichiers sources, vous pouvez aller dans ce r&eacute;pertoire et &eacute;xecuter
les commandes cvs, sans le marqueur -d. Par exemple :
<BR><B><I>cvs update</I></B>
</UL>

<P><B>Documentations compl&eacute;mentaires</B>
<UL>
<LI><A href="http://cvsbook.red-bean.com/">The CVS Book</A>
<LI><A href="http://www.loria.fr/~molli/cvs/doc/cvs_toc.html">Docs CVS sur www.loria.fr</A>
</UL>

<?php
$HTML->footer(array());

?>
