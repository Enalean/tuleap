#!/usr/bin/perl 

# Component for extracting information from CodeX database
# Main package
# Created by Vincent GOUDE <vincent.goude@kereval.com>
# Updated by Jacques Chauvin <jacq.chauvin@orange-ftgroup.com>
# Updated by Giulio Iannazzo <giulio.iannazzo@xrce.xerox.com>

use CGI;
use CGI qw(:standard);
use CGI::Carp qw(fatalsToBrowser);
my $cgi = new CGI;

use strict;
use warnings;
use lib qw(/home/httpd/perl/);
use lib qw(/usr/share/codendi/tools/utils/stats/);
use Getopt::Long;
use Time::Local;
use Carp;
use DBI; #database library
use SQLmetrics; #package/class SQLmetrics, definition and use of metrics for this project
use POSIX;
use HTML::Entities ();

my $INSTALL_DIR="/usr/share/codendi";
require("$INSTALL_DIR/src/utils/include.pl");  # Include all the predefined functions and variables

use vars qw($dbh);
my $sys_dbhost;
my $sys_dbname;
my $sys_dbuser;
my $sys_dbpasswd;
my $version="1.0";					# script version
my $date_end;                              	# base date and end of the period
my $date_start;					# date of the period start
my %Codex_metrics;					# hash[id_projet|%project_metrics], hasmap linking an id_project with a hash [metric, value]
my $file_prefix="IndicateursCodex";		# prefix of the output file

##############################
# Database Connect Functions
##############################
sub db_connect {
	# connect to the database
	$dbh ||= DBI->connect("DBI:mysql:$sys_dbname:$sys_dbhost", "$sys_dbuser", "$sys_dbpasswd");
}

############################################################
###Check Arguments and configuration of the script
############################################################
my @tmp_localtime;# variable temporaire pour le stockage de date

#initialisation de la date
my ($s_mday,$s_mon,$s_year) = split("\/",$cgi->param("date_start")); 
my ($e_mday,$e_mon,$e_year) = split("\/",$cgi->param("date_end")); 
my $time_end;
my $time_start;

#Contrôle de la date de debut si elle existe
if($s_mday)
{
	($s_mon=~ m/^\d+$/ and $s_mon>0 and $s_mon<13) or die "date parameter invalid. Month out of range 1..12 \n";
	($s_mday=~ m/^\d+$/ and $s_mday>0 and $s_mday<32) or die "date parameter invalid. Day out of range 1..31 \n";
	($s_year=~ m/^d*[1-9]+\d*$/) or die "date parameter invalid. Year is invalid.\n";
}
#Contrôle de la date de fin si elle existe
if($e_mday)
{
	($e_mon=~ m/^\d+$/ and $e_mon>0 and $e_mon<13) or die "date parameter invalid. Month out of range 1..12 \n";
	($e_mday=~ m/^\d+$/ and $e_mday>0 and $e_mday<32) or die "date parameter invalid. Day out of range 1..31 \n";
	($e_year=~ m/^d*[1-9]+\d*$/) or die "date parameter invalid. Year is invalid.\n";
}

if($s_mday && $e_mday)#si les deux dates sont correctement renseignees calcul des dates de debut et date de fin
{
	$time_end = timelocal(0,0,0,$e_mday,$e_mon-1,$e_year-1900);	
	$time_start = timelocal(0,0,0,$s_mday,$s_mon-1,$s_year-1900);
}
elsif ($e_mday)#si seulement la date de fin est renseigner on utilise la periode par defaut de 30 jours pour calculer la date de debut
{
	$time_end = timelocal(0,0,0,$e_mday,$e_mon-1,$e_year-1900);	

	$time_start = $time_end- 86400*30;
	#$time_start parsing for $cvs_period
	@tmp_localtime = localtime($time_start);
	($s_mon,$s_mday,$s_year)=($tmp_localtime[4]+1,$tmp_localtime[3],1900+$tmp_localtime[5]);
}
elsif ($s_mday)#si seulement la date de debut est renseigner on utilise la periode par defaut de 30 jours pour calculer la date de fin
{
	$time_start = timelocal(0,0,0,$s_mday,$s_mon-1,$s_year-1900);

	$time_end = $time_start + 86400*30;
	#$time_end parsing for $cvs_date
	@tmp_localtime = localtime($time_end);
	($e_mon,$e_mday,$e_year)=($tmp_localtime[4]+1,$tmp_localtime[3],1900+$tmp_localtime[5]);
} else#sinon on part de la date courante jusqu'à il y a 30 jours
{
	@tmp_localtime = localtime(time);
	($e_mon,$e_mday,$e_year)=(($tmp_localtime[4]+1),$tmp_localtime[3],(1900+$tmp_localtime[5]));#today as default
	$time_end = timelocal(0,0,0,$e_mday,$e_mon-1,$e_year-1900);	

	$time_start = $time_end- 86400*30;
	#$time_start parsing for $cvs_period
	@tmp_localtime = localtime($time_start);
	($s_mon,$s_mday,$s_year)=($tmp_localtime[4]+1,$tmp_localtime[3],1900+$tmp_localtime[5]);	
}

#contrôle des time_ends time_end>time_start
if($time_end<$time_start)#on inverse les dates
{
	my $temp_stamp=$time_end;
	$time_end=$time_start;
	$time_start=$temp_stamp;
	my($temp_mon,$temp_mday,$temp_year)=($e_mon,$e_mday,$e_year);
	($e_mon,$e_mday,$e_year)=($s_mon,$s_mday,$s_year);
	($s_mon,$s_mday,$s_year)=($temp_mon,$temp_mday,$temp_year);
	

}

my $period=ceil(($time_end-$time_start)/86400)+1;

#$time_start parsing for $cvs_period
my $cvs_time_end = "$e_year".sprintf("%.2i",$e_mon).sprintf("%.2i",$e_mday);#date format used with cvs_history table
my $cvs_time_start = "$s_year".sprintf("%.2i",$s_mon).sprintf("%.2i",$s_mday);#date format used with cvs_history table



############################################################
### requests and metrics identification
############################################################

my(@Allmetrics);

#Indicateur 'Projet Codex', recupère les noms et les identifiants des projets actifs, crees avant la date de fin. 
#On exclut le projet 614 (indicateurs Codex)
#date de creation du projet infere à partir du plus ancien evenement enregistrer dans l'historique du projet
#Attention au changement du nom de la metrique "Projet Codex", ce nom est utilise pour effectue un traitement supplementaire après execution de cette requête
#push(@Allmetrics,new SQLmetrics("Projet Codex",
#"SELECT g.group_id, group_name FROM groups g, group_history gh
#WHERE g.group_id=gh.group_id AND gh.date<=$time_end 
#GROUP BY g.group_id"));

push(@Allmetrics,new SQLmetrics("Projet Codex",
"SELECT group_id, group_name FROM
 groups WHERE status='A'  
AND register_time<=$time_end
AND group_id!=614  
GROUP BY group_id;"));


push(@Allmetrics,new SQLmetrics("Description",
"SELECT group_id, REPLACE(REPLACE (short_description, CHAR(13),' '),CHAR(10),' ') FROM groups
WHERE status='A' AND register_time<=$time_end 
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Cree le",
"SELECT group_id, FROM_UNIXTIME(register_time,'%Y-%m-%d')  FROM groups
WHERE status='A' AND register_time<=$time_end 
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Organisation",
"SELECT tgl.group_id, tc.shortname  FROM trove_group_link tgl, trove_cat tc
WHERE tgl.trove_cat_root='281' AND tc.root_parent=tgl.trove_cat_root AND tc.trove_cat_id=tgl.trove_cat_id
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Administrateur",
"SELECT g.group_id, u.user_name  FROM user_group g, user u
WHERE g.user_id=u.user_id AND u.status='A'
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Nom",
"SELECT g.group_id, u.realname  FROM user_group g, user u
WHERE g.user_id=u.user_id AND u.status='A'
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Email",
"SELECT g.group_id, u.email  FROM user_group g, user u
WHERE g.user_id=u.user_id AND u.status='A'
GROUP BY group_id"));

#push(@Allmetrics,new SQLmetrics("Other_Comments",
#"SELECT group_id, REPLACE(REPLACE (other_comments, CHAR(13),' '),CHAR(10),' ') FROM groups
#WHERE status='A' AND register_time<=$time_end 
#GROUP BY group_id"));

#Calcul de l'indicateur 'Activite CVS' par projet
#date de "l'activite CVS":  group_cvs_full_history.day
#TODO Activite CVS evaluer la necessite d'inclure d'autre champ dans le calcul exemple: cvs_adds, cvs_checktout, cvs_browse
push(@Allmetrics,new SQLmetrics("Activite CVS",
"SELECT group_id, SUM(cvs_commits) 
FROM group_cvs_full_history
WHERE day<=$cvs_time_end AND day>=$cvs_time_start
GROUP BY group_id"));

#Calcul de l'indicateur 'Activite SVN' par projet
#date de "creation du fichier":  group_svn_full_history.day
#TODO Activite SVN evaluer la necessite d'inclure d'autre champ dans le calcul exemple: svn_adds, svn_checktout, svn_browse, svn_commit
#TODO Activite SVN contrôler la pertinence de la requete sur le serveur de prod
#TODO renommer l'indicateur commit SVN
#NOTE: les champs svn_commit... ne sont pas renseigner dans la base
push(@Allmetrics,new SQLmetrics("ActiviteSVN",
"SELECT group_id,COUNT(*) 
FROM  svn_commits 
WHERE date<=$time_end AND date>=$time_start
GROUP BY group_id"));

push(@Allmetrics,new SQLmetrics("Push Git",
"SELECT project_id, count(*)
FROM  plugin_git_log INNER JOIN plugin_git USING(repository_id)
WHERE push_date<=$time_end AND push_date>=$time_start
GROUP BY project_id"));

#Calcul de l'indicateur 'Fichiers publies' par projet
#date de "creation du fichier":  frs_file.postdate
push(@Allmetrics,new SQLmetrics("Fichiers publie","SELECT p.group_id, COUNT(file_id ) 
FROM frs_file f,frs_package p,frs_release r 
WHERE f.release_id= r.release_id AND r.package_id= p.package_id AND f.post_date<=$time_end
AND f.post_date>=$time_start
GROUP BY p.group_id"));

#Calcul de l'indicateur 'Fichiers publies (total)' par projet
#date de "creation du fichier":  frs_file.postdate
push(@Allmetrics,new SQLmetrics("Fichiers publies (total)","SELECT p.group_id, COUNT( DISTINCT file_id ) 
FROM frs_file f,frs_package p,frs_release r 
WHERE f.release_id = r.release_id AND r.package_id = p.package_id AND f.post_date<=$time_end
GROUP BY p.group_id"));

#Calcul de l'indicateur 'fichiers telecharges (total)' par projet
#date de "creation du fichier":  frs_file.postdate
push(@Allmetrics,new SQLmetrics("Fichiers telecharges (total)","SELECT p.group_id, COUNT(filerelease_id ) 
FROM filedownload_log l,frs_package p,frs_release r 
WHERE l.filerelease_id = r.release_id AND r.package_id = p.package_id AND l.time<=$time_end
GROUP BY p.group_id"));

#Calcul de l'indicateur 'Telechargements (periode X jours)' par projet
#date de "telechargement": frs_dlstats_file_agg.day
push(@Allmetrics,new SQLmetrics("Telechargements",
"SELECT p.group_id,SUM(downloads ) 
FROM frs_dlstats_file_agg fdl, frs_file f,frs_package p,frs_release r
WHERE fdl.file_id=f.file_id AND f.release_id = r.release_id AND r.package_id = p.package_id 
AND fdl.day<=$cvs_time_end AND fdl.day>=$cvs_time_start
GROUP BY p.group_id"));

#Calcul de l'indicateur 'Listes de diffusion actives' par projet
#valeur des listes detruites: is_public=9
#TODO date de "creation de la liste":  ?
push(@Allmetrics,new SQLmetrics("Listes de diffusion actives","SELECT group_id, COUNT( DISTINCT group_list_id ) 
FROM mail_group_list
WHERE is_public!=9
GROUP BY group_id
"));

#Calcul de l'indicateur 'Listes de diffusion inactives' par projet
#valeur des listes detruites: is_public=9
#TODO date de "creation de la liste":  ?
push(@Allmetrics,new SQLmetrics("Listes de diffusion inactives","SELECT group_id, COUNT( DISTINCT group_list_id ) 
FROM mail_group_list
WHERE is_public=9
GROUP BY group_id"));

#Calcul de l'indicateur 'Forums actifs' par projet
#date de "creation du forum", filtrer les forums n'ayant pas de message avant la date $date dans la table forum
#NOTE: Le terme 'actif' est trompeur on ne controle pas l'activite du forum mais seulement la presence d'un message avant la date nom de projet 0
push(@Allmetrics,new SQLmetrics("Forums actifs",
"SELECT group_id,COUNT( DISTINCT fg.group_forum_id ) 
FROM forum_group_list fg, forum f
WHERE fg.group_forum_id =f.group_forum_id  
AND f.date<=$time_end AND fg.is_public != 9 
GROUP BY  fg.group_id"));

#Calcul de l'indicateur 'Forums inactifs' par projet
push(@Allmetrics,new SQLmetrics("Forums inactifs",
"SELECT group_id,COUNT( DISTINCT fg.group_forum_id ) 
FROM forum_group_list fg, forum f
WHERE fg.group_forum_id =f.group_forum_id  
AND f.date<=$time_end AND fg.is_public = 9 
GROUP BY  fg.group_id"));


#Calcul de l'indicateur 'Activites Forum' par projet
#Nombre de message poster sur tout les forums du projet depuis X jours
#date de "creation du forum", filtrer les forums n'ayant pas de message avant la date $date dans la table forum
push(@Allmetrics,new SQLmetrics("Activites Forum",
"SELECT group_id,COUNT(DISTINCT f.msg_id ) 
FROM forum_group_list fg, forum f
WHERE fg.group_forum_id =f.group_forum_id  AND f.date<=$time_end AND f.date>=$time_start
GROUP BY  fg.group_id"));

#Calcul de l'indicateur 'Documents wiki' par projet
#date de "creation du document": ? pas de moyens consistant les documents et les pages sont relativement decoreles
#Il existe un document acceuil cree automatiquement dès que le wiki est initialise
push(@Allmetrics,new SQLmetrics("Documents wiki",
"SELECT group_id, COUNT( DISTINCT id) FROM wiki_group_list GROUP BY group_id"));

#Calcul de l'indicateur 'Pages modifies (periode X jours)' par projet
#date de "creation de la page":plus vieux temps time
#TODO Tenir compte du problème des pages creees par defaut!
push(@Allmetrics,new SQLmetrics("Pages modifiees",
"SELECT group_id, COUNT(pagename) FROM wiki_log 
WHERE time<=$time_end AND time>=$time_start
GROUP BY group_id"));

#Calcul de l'indicateur 'page wiki' par projet
#date de "creation de la page":plus vieux temps time
#TODO Tenir compte du problème des pages creees par defaut!
push(@Allmetrics,new SQLmetrics("Pages wiki (total)",
"SELECT group_id, COUNT( DISTINCT pagename) FROM wiki_log 
WHERE time<=$time_end
GROUP BY group_id"));

# Calcul de l'indicateur 'Artifacts ouverts' par projet

push(@Allmetrics,new SQLmetrics("Artifacts ouverts",
"SELECT artifact_group_list.group_id,
COUNT(artifact.artifact_id)
FROM artifact_group_list, artifact
WHERE ( open_date >= $time_start AND open_date < $time_end AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
GROUP BY artifact_group_list.group_id"));

# Calcul de l'indicateur 'Artifacts fermes' par projet

push(@Allmetrics,new SQLmetrics("Artifacts fermes",
"SELECT artifact_group_list.group_id,
COUNT(artifact.artifact_id)
FROM artifact_group_list, artifact
WHERE ( close_date >= $time_start 
AND close_date < $time_end 
AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
GROUP BY artifact_group_list.group_id"));

# Calcul de l'indicateur 'Utilisateurs ajoutes' par projet

push(@Allmetrics,new SQLmetrics("Utilisateurs ajoutes",
"SELECT group_id,COUNT(u.user_id) 
FROM user_group ug, user u 
WHERE u.user_id = ug.user_id
AND add_date>=$time_start
AND add_date<=$time_end
GROUP BY  group_id"));

# Extraction du champ 'Code projet' par projet

push(@Allmetrics,new SQLmetrics("Code projet",
"select g.group_id, value from groups g,group_desc_value gdv, group_desc gd 
WHERE g.group_id = gdv.group_id
AND gdv.group_desc_id = gd.group_desc_id 
AND gd.desc_name = 'Code projet'
AND register_time<=$time_end 
GROUP BY g.group_id"));

# Calcul de l'indicateur 'Documents ajoutés' par projet

push(@Allmetrics,new SQLmetrics("Documents ajoutes",
"select group_id, COUNT(item_id) FROM plugin_docman_item
WHERE create_date >=$time_start
AND create_date <=$time_end
GROUP BY  group_id"));

# Calcul de l'indicateur 'Documents effacés' par projet

push(@Allmetrics,new SQLmetrics("Documents effaces",
"select group_id, COUNT(item_id) FROM plugin_docman_item
WHERE delete_date >=$time_start
AND delete_date <=$time_end
GROUP BY  group_id"));

# Calcul de l'indicateur 'News publiées' par projet

push(@Allmetrics,new SQLmetrics("News publiees",
"select group_id, COUNT(id) FROM news_bytes
WHERE date >=$time_start
AND date <=$time_end
GROUP BY  group_id"));

# Calcul de l'indicateur 'Sondages actif' par projet

push(@Allmetrics,new SQLmetrics("Sondages actif",
"select g.group_id, COUNT(survey_id) FROM surveys s, groups g
WHERE is_active = 1
AND g.group_id = s.group_id
GROUP BY  g.group_id"));

# Calcul de l'indicateur 'Réponses aux sondages publiées' par projet

push(@Allmetrics,new SQLmetrics("Reponses sondages",
"select group_id, COUNT(*) FROM survey_responses
WHERE date >=$time_start
AND date <=$time_end
GROUP BY  group_id"));

# Verifier si le service 'Integration Continue' est activé par projet

push(@Allmetrics,new SQLmetrics("IntegrationContinueActive",
"select group_id, is_used from service 
WHERE short_name = 'hudson'
GROUP BY  group_id"));

# Calcul des jobs existants pour le service 'Integration Continue' par projet

push(@Allmetrics,new SQLmetrics("IntegrationContinueJobs",
"select group_id, COUNT(*) from plugin_hudson_job
GROUP BY  group_id"));


############################################################
###Connect to database and requests process
############################################################
&db_connect();
my $metrics;
foreach $metrics (@Allmetrics)
{
	my $request_result=$metrics->processRequest($dbh);
	
	while( my @sql_res = $request_result -> fetchrow_array()) {
		if ($metrics->{NAME} eq "Projet Codex") #ajout de " " au nom du projet pour identifier correctement le nom du projet dans les sorties
			{
			 $sql_res[1]="\"".$sql_res[1]."\"";			
			}
		elsif ($metrics->{NAME} eq "Description") #ajout de " " pour identifier correctement la description du projet dans les sorties
			{
			 $sql_res[1]="\"".$sql_res[1]."\"";			
			}	
		elsif ($metrics->{NAME} eq "IntegrationContinueActive") #on remplace 1/0 avec oui/non pour IntegrationContinueActive 
			{
			 if ($sql_res[1] == 1)
			 {
			 	$sql_res[1] = 'oui';
			 }
			 elsif ($sql_res[1] == 0)
			 {
			 	$sql_res[1] = 'non';
			 }
			}
		storeMetric($sql_res[0],$metrics->{NAME},$sql_res[1]);
	}
}

############################################################
###Output Results
############################################################
print $cgi->header('text/plain');
#$cgi->start_html("IndicateursCodex-$b_year$b_mon$b_mday\_$period.csv");
print "Date de debut : $s_mday/$s_mon/$s_year,\n";
print "Date de fin : $e_mday/$e_mon/$e_year,\n";

my $print_id;#id of the project to print
#print "******************SortieCSV**************************\n";
#open(FILE,">$file_prefix-$b_year$b_mon$b_mday\_$period.csv");
#print FILE projectMetrics2CSV(\@Allmetrics)."\n";
print projectMetrics2CSV(\@Allmetrics)."\n";
foreach $print_id (keys(%Codex_metrics))
{
	if (defined(${$Codex_metrics{$print_id}}{"Projet Codex"}))#Si le projet à un nom (i.e n'a pas ete filtre)
	{	
		print projectValues2CSV($print_id,\@Allmetrics)."\n";
		#print FILE projectValues2CSV($print_id,\@Allmetrics)."\n";
	}	
	
}
;


#print $cgi->end_html;
#close FILE;








##########################################################
# store the result of a metrics for a given project
##########################################################
sub storeMetric
{
 my $project_id=shift;#identifiant du projet	
 my $metric=shift;#nom de la metrique/indicateur
 my $value=shift;#valeur de la metrique/indicateur
 my %temp_ProjectMetrics;
 #verifier que le hashmap %Codex_metrics contient la clef $id_projet et que sa valeur est un hashmap
 if (defined($Codex_metrics{$project_id}))#TODO storemetric verifier que $Codex_metrics{$project_id} est une hashmap
{
	%temp_ProjectMetrics=%{$Codex_metrics{$project_id}};
}
 #ajouter le couple [$metric|$value] au hashmap associe à $id_projet dans %Codex_metrics
$temp_ProjectMetrics{$metric}=$value;
$Codex_metrics{$project_id}={%temp_ProjectMetrics};
}

##########################################################
#Return the metrics values for a given project in CSV form
##########################################################
sub projectValues2CSV
{
my $project_id=$_[0];#identifiant du projet
my @Allmetrics=@{$_[1]};
my $strCSV="";#chaine stockant sous forme CSV les differentes valeurs de metrics
my $tmpStrCSV="";#chaine stockant la valeur d'une metrique avant nettoyages des characteres 

 if (defined($Codex_metrics{$project_id}))#TODO projectvalues2CSV verifier que $Codex_metrics{$project_id} est une hashmap
{
	my %temp_ProjectMetrics=%{$Codex_metrics{$project_id}};
	my $metrics;
	

		foreach $metrics (@Allmetrics)
		{
			if (defined($temp_ProjectMetrics{$metrics->{NAME}})) #si la metric n'est pas defini pour ce projet, resultat=0
			{
				$tmpStrCSV = $temp_ProjectMetrics{$metrics->{NAME}};
				$tmpStrCSV =~ s/\r\n?//g; # on enleve tous les retours à la ligne 
				$tmpStrCSV = HTML::Entities::decode($tmpStrCSV); # on transcode de html à ISO-8859/1
				$strCSV=$strCSV.$tmpStrCSV."|";
			}
			else
			{
				$strCSV=$strCSV."0|";
			}
		}
		
}
else
{
	print "projectValues2CSV ERROR: can't find project $project_id"
};	
return $strCSV;
}

##########################################################
#Return the metrics title for a model project 
##########################################################
sub projectMetrics2CSV
{
my @Allmetrics=@{$_[0]};
my $strCSV="";#chaine stockant sous forme CSV les differentes valeurs de metrics
my $metrics;

foreach $metrics (@Allmetrics)#TODO: integrer un trie custom pour la sortie des metrics
	{
		$strCSV=$strCSV.$metrics->{NAME}."|";
		
	}
return $strCSV;	
}


sub version {
  print "IndicateursCodex.pl version: $version\n";
  exit 0;
};

sub help {
  print "IndicateursCodex.pl : copyright (c) 2006 - vincent GOUDE <vincent.goude\@kereval.com>\n";
  print "\n";
  print "    Extracting indicators from CodeX sourceforge database \n";
  print "\n";
  print "   usage:\n";
  print "\n";
  print "     --version  (-v) : script version\n";
  print "     --help     (-h) : this help\n";
  print "     --date=     (-d=) : \"<date>\" as <month>/<day>/<year>, by default today [optional]\n";
  print "     --period=   (-p=) : \"<period>\" days, by default 30 days [optional]\n";
  print "     --file=   (-f) : \"<file>\" prefix of the output file, by default IndicateursCodex[optional]\n";
  exit 0;
}

