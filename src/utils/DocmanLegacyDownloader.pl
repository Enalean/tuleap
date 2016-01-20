#!/usr/bin/perl
#######################################################################
###                  DocmanLegacyDownloader.pl                      ###
#######################################################################
#                                                                     #
# Script to download documents from the legacy docman to              #
# 'project_documentation' dir.                                        #
#                                                                     #
# you will be asked to type                                           #
#   -user: the user to access the database                            #
#   -password                                                         #
#   -group_id is the group_id of your project                         #
#                                                                     #
# NB: if you are not using this script on codendi.cro.st.com            #
#     you have to change in the line 41 'host=codendi.cro.st.com'       #
#     by your server name.                                            #
#                                                                     #
# Author : Hamouda LAYOUNI                                            #
# email  : hamouda.layouni@st.com                                     #
#######################################################################

use strict;
use warnings;
use DBI;
use Term::ReadKey;

# See counter part in php (src/www/utils.php)
sub util_unconvert_htmlspecialchars {
    my ($data) = @_;
    $data =~ s/&nbsp;/ /gi;
    $data =~ s/&quot;/"/gi;
    $data =~ s/&gt;/>/gi;
    $data =~ s/&lt;/</gi;
    $data =~ s/&amp;/&/gi;
    return $data;
}

my $_codendi_server="codendi-test.cro.st.com";
my $_codendi_db="codendi";

print "Database User : ";
ReadMode('normal');
my $user = ReadLine(0);
chomp ($user);

print "Password for $user :";
ReadMode('noecho');
my $passwd = ReadLine(0);
chomp ($passwd);

print "\nEnter the group_id of the project :";
ReadMode('normal');
my $group_id = ReadLine(0);
chomp ($group_id);
my $source ="dbi:mysql:dbname=$_codendi_db;host=$_codendi_server";
my $base = DBI->connect($source,$user,$passwd) or die DBI::errstr;

print "Creating directory: project_documentation \n";
mkdir "project_documentation", 0755;
chdir ('project_documentation');

my $sql_doc_groups = "select * from doc_groups where group_id = $group_id";


my $req_doc_groups=$base->prepare($sql_doc_groups) or die($base->errstr());
$req_doc_groups->execute() or die($base->errstr());

while (my @z =$req_doc_groups->fetchrow_array())
{
    print " ---- Creating directory :$z[1]  \n";
    mkdir "$z[1]", 0755;
    chdir ("$z[1]");
    my $sql = "select data,filename,title,filetype from doc_data where doc_group=$z[0]";
    my $req=$base->prepare($sql) or die($base->errstr());
    $req->execute() or die($base->errstr());
    while (my ($data, $filename, $title, $filetype) = $req->fetchrow_array())
    {
	my $fname;
	if($filename ne "") {
	    $fname = $filename;
	} else {
	    # No filename, we are probably in a context of copied/pasted file.
	    $fname = $title;
	    if($filetype eq 'text/html') {
		$fname .= ".html";
		# Need to unconver html special char (stored encoded in DB)
		$data = util_unconvert_htmlspecialchars($data);
	    }
	    elsif($filetype eq 'text/plain') {
		$fname .= ".txt";
		# Need to unconver html special char (stored encoded in DB)
		$data = util_unconvert_htmlspecialchars($data);
	    }
	    else {
		print "Unknown file type: ".$filetype."\n";
	    }
	}
        print " -------- Creating file : ".$fname." \n";
        open(FILE,">$fname");
        binmode(FILE);
        print FILE $data;
        close(FILE);
    }
    chdir ('../');
}

