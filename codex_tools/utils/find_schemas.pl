#! /usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2003-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# $Id$
#
# This program scans a CodeX CVS repository for XML Schema files
# and generates two reports:
#
# (1) A summary of all changes since the last time the program
#     was run.  This is emailed to a single "administrator".
#
# (2) A summary of schema files in a project.  This is emailed
#     to the administrators of that project along with some
#     information about the Xerox schema repository.  This
#     information is resent if a sufficiently long time has
#     passed since the last mailing.
#
# See the "help_message" function for more information about this
# program.
#

use URI::Escape;
use Net::SMTP;
use MIME::QuotedPrint;
use DBI;
use DB_File;
use Fcntl qw(:DEFAULT :flock);
use Getopt::Long;

#
# Configuration
#
my $codex = "";
my $config = "";
my $schema_db_file = "";
my $message_file = "";
my $from_address = "";
my $admin_address = "";
my $override_address = "";
my $mailhost = "";
my $cvs_prefix = "";
my $import = "";
my $export = "";
my $target_suffix = "";
my $resend_interval = 0;
my $debug = 0;

GetOptions("codex=s" => \$codex,
	   "config=s" => \$config,
	   "cvs=s" => \$cvs_prefix,
	   "database=s" => \$schema_db_file,
	   "message=s" => \$message_file,
	   "from=s" => \$from_address,
	   "admin=s" => \$admin_address,
	   "override=s" => \$override_address,
	   "mailhost=s" => \$mailhost,
	   "import=s" => \$import,
	   "export=s" => \$export,
	   "target=s" => \$target_suffix,
	   "resend_seconds=i" => \$resend_interval,
	   "resend_days=i" => sub {
	       my ($name, $value) = @_;
	       $resend_interval = int($value * 24 * 60 * 60);
	   },
	   "help" => \&help_message,
	   "debug+" => \$debug
	   ) || die "Could not parse options";


$codex = '/home' unless $codex;
$cvs_prefix = '/cvsroot' unless $cvs_prefix;
$schema_db_file = "$codex/var/codex_schemas.db" unless $schema_db_file;
$message_file = "$codex/var/codex_schemas.html" unless $message_file;
$from_address = 'noreply@codex.xerox.com' unless $from_address;
$mailhost = 'mailhost.wrc.xerox.com' unless $mailhost;
$resend_interval = (365 / 2) * 24 * 60 * 60 unless $resend_interval;
$target_suffix = ".xsd" unless $target_suffix;

unless ($config) {
    if (-f "$codex/etc/local.inc") {
	$config = $codex;
    }
    elsif (-f "/etc/local.inc") {
	$config = "";
    }
    else {
	die "Cannot locate local.inc at either $codex/etc/local.inc or /etc/local.inc";
    }
}

#
# Derived configuration variables
#

my $utils = "$codex/httpd/SF/utils";
my $schema_lock_file = $schema_db_file . ".lock";

if ($debug) {
    print "codex = $codex\n";
    print "config = $config\n";
    print "cvs_prefix = $cvs_prefix\n";
    print "schema_db_file = $schema_db_file\n";
    print "schema_lock_file = $schema_lock_file\n";
    print "message_file = $message_file\n";
    print "from_address = $from_address\n";
    print "admin_address = $admin_address\n";
    print "override_address = $admin_address\n";
    print "mailhost = $mailhost\n";
    print "import = $import\n";
    print "export = $export\n";
    print "target_suffix = $target_suffix\n";
    print "resend_interval = ", $resend_interval, " (", sprintf("%0.2f", $resend_interval / (24 * 60 * 60)), " days)\n";
    print "utils = $utils\n";
}

$ENV{'SF_LOCAL_INC_PREFIX'} = $config;
require "$utils/include.pl";
&db_connect || die "Could not connect to CodeX database";

my $mail_subject = 'The XSD files in the CodeX repository have changed';
my $project_http_prefix = "http://${sys_default_domain}";
my $cvs_http_prefix = "http://${sys_default_domain}/cgi-bin/cvsweb.cgi";

if ($debug) {
    print "sys_default_domain = $sys_default_domain\n";
}

#
# Some constants
#

my $cvs_prefix_pattern = $cvs_prefix;
$cvs_prefix_pattern =~ s/(\W)/\\\1/g;	# quote regex chars

my $DAY = 60*60*24;                     # seconds in a day

my $fudge_max = $resend_interval * 0.25;
if ($fudge_max > 10 * $DAY) {
    $fudge_max = 10 * $DAY;
}
 
#
# And some globals
#

my $current_time = time();
my %info;
my $DB;

#
# A help message
#
sub help_message {
    print STDERR <<EOF;
find_schemas.pl [options]
Options are:

    codex=path       The top level CodeX directory.
    config=path      Prefix this to '/etc/local.inc' to find the
                     CodeX config file.
    cvs=path         The root of the CodeX CVS tree.
    database         Where find_schemas should keep its data.
    message          The html body fragment that will be sent
                     to each project leader.
    from=email       The mail address to stick in the 'from' field.
    admin=email      The address to send summary info to (optional).
    override=email   Send mail here instead of project admins.
    mailhost=dns     The SMTP server to connect to to send mail.
    import=txtfile   Import the database from the text file and quit.
    export=txtfile   Export the database to the text file and quit.
    target=s         Debugging: use s instead of '.xsd' when
                     searching for files.
    resend_seconds=s How long to wait (in seconds) before
                     resending notifications.
    resend_days=d    Like resend_seconds, but in days.
    help             Print this message.
    debug            Turn on some debugging output.

Specifying --debug more than once may increase the level of
debugging detail.
EOF

exit 0;
}

#
# Return the list of matching files as an array
#
sub find_targets {
    my ($root, $closure) = @_;
    my $pattern;
    
    if ($debug) {
	print "DBG: find_targets with suffix $target_suffix, initial root = $root\n";
    }

    #
    # Compile the search pattern and validate the root
    #
    ($pattern = "${target_suffix},v") =~ s/(\W)/\\\1/g;
    $pattern = "${pattern}\$";
    $pattern = qr/$pattern/;

    if (! -d $root) {
	print "$root is not a directory.\n";
	return 0;
    }


    #
    # Expand the queue
    #
    my @queue = ($root);
    while ($#queue >= 0) {
        my $dir = pop(@queue);
	if ($debug > 1) {
	    print "DBG: dir='", $dir, "', queue='", join(", ", @queue), "\n";
	}

	unless(opendir(DIR,$dir)) {
	    print "Cannot enter $dir, skipping...\n";
	    next;
	}
	my @filenames = readdir(DIR);
	closedir(DIR);

	for (@filenames) {
	    my $path = $dir . "/" . $_;
	    if (m/$pattern/ && -f $path) {
		$closure->($path);
	    }
	    elsif (-d $path) {
		next if $_ eq ".";
		next if $_ eq "..";
		next if $_ eq "Attic";
		push(@queue,$path);
	    }
	}
    }
    return 1;
}

#
# Decompse a path rooted at $cvs_prefix into a project and a pathname
#
sub decompose_cvs_path {
    local ($_) = @_;

    /^${cvs_prefix_pattern}\/([^\/]*)\/(.*),v$/ && ($1,$2);
}

#
# Create a "cvsweb" URI to the given project and pathname.
#
sub create_cvsweb_uri {
    my ($project,$path) = @_;

    $project = uri_escape($project);
    $path = uri_escape($path,"^A-Za-z0-9/\-_.!~*'\"()");

    "${cvs_http_prefix}/$path?cvsroot=${project}";
}

#
# Create a "project" URI to the given project.
#
sub create_project_uri {
    my ($project) = @_;

    $project = uri_escape($project);

    "${project_http_prefix}/projects/${project}";
}


#
# Send a mail message
#
sub send_admin_email {
    my ($from, $to, $subject, $intro, $all_files) = @_;

    my $smtp;
    my $time = time();
    my $content_id = "<log_message_from_codex_${time}@${sys_cvs_host}>";
    my $boundary = "log.message.from.codex.${time}@${sys_cvs_host}";
    my $content_id_0 = "<log_message_from_codex_${time}_0@${sys_cvs_host}>";
    my $content_id_1 = "<log_message_from_codex_${time}_1@${sys_cvs_host}>";

    $smtp = Net::SMTP->new($mailhost);
    unless ($smtp) {
    	print "Could not open a SMTP connection.\n";
	return 0;
    }

    $smtp->mail($from);
    $smtp->to($to);

    $smtp->data();
    $smtp->datasend("MIME-Version: 1.0\n");
    $smtp->datasend("To: " . join(", ", $to) . "\n");
    $smtp->datasend("Subject: ${subject}\n");
    $smtp->datasend("From: " . join(", ", $from) . "\n");
    $smtp->datasend("Content-ID: ${content-id}\n");
    $smtp->datasend("Content-type: multipart/mixed;\n");
    $smtp->datasend("\tboundary=${boundary}\n");
    $smtp->datasend("\n");
    $smtp->datasend("This is  a multimedia message in MIME  format.  If you are reading this ");
    $smtp->datasend("prefix, your mail reader does  not understand MIME.  You may wish ");
    $smtp->datasend("to look into upgrading to a newer version of  your mail reader.\n");
    $smtp->datasend("\n");
    $smtp->datasend("--${boundary}\n");
    $smtp->datasend("Content-ID: ${content_id_0}\n");
    $smtp->datasend("Content-type: text/html\n");
    $smtp->datasend("Content-Description: schema_changes.html\n");
    $smtp->datasend("Content-Transfer-Encoding: quoted-printable\n");
    $smtp->datasend("\n");

    foreach (@$intro) {
        $smtp->datasend(encode_qp($_));
    }
    $smtp->datasend("\n\n");
    $smtp->datasend("--${boundary}\n");
    $smtp->datasend("Content-ID: ${content_id_1}\n");
    $smtp->datasend("Content-type: text/html\n");
    $smtp->datasend("Content-Description: full_schema_list.html\n");
    $smtp->datasend("Content-Transfer-Encoding: quoted-printable\n");
    $smtp->datasend("\n");
    $smtp->datasend("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 TRANSITIONAL//EN\">\n");
    $smtp->datasend("<html>\n");
    $smtp->datasend("<head>\n");
    $smtp->datasend("<title>Listing of XSD Files</title>\n");
    $smtp->datasend("<meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\">\n");
    $smtp->datasend("</head>\n");
    $smtp->datasend("<body>\n");


    my $message = compose_file_list("h1", 1, $all_files);
    foreach (@$message) {
        $smtp->datasend(encode_qp($_));
    }
    $smtp->datasend("</body>\n");
    $smtp->datasend("</html>\n");
    $smtp->datasend("--${boundary}--\n");
    $smtp->dataend();

    $smtp->quit;

    return 1;
}

#
# Compose a file list in HTML, return a reference to a list of lines.
#
sub compose_file_list {
    my ($header, $hyperlink, $list) = @_;
    my $last_project;
    my @message;

    foreach (@$list) {
	my ($project,$path) = decompose_cvs_path($_);
	my $line;
	if ($project ne $last_project) {
	    my $project_uri = create_project_uri($project);
	    $line = "<${header}><a href=\"${project_uri}\">Project ${project}</a></${header}>\n";
	    push(@message, "</ul>\n") if $last_project;
	    push(@message, $line);
	    push(@message, "<ul>\n");
	    $last_project = $project;
	}
	if ($hyperlink) {
	    my $cvs_uri = create_cvsweb_uri($project, $path);
	    $line = "  <li><a href=\"${cvs_uri}\">${path}</a></li>\n";
	}
	else {
	    $line = "  <li>${path}</li>\n";
	}
	push(@message, $line);
    }
    push(@message, "</ul>\n") if $last_project;

    \@message;
}

#
# Compose the introductory message, return a reference to a list of lines.
#
sub intro_message {
    my ($added_ref, $removed_ref) = @_;
    my @added = @{$added_ref};
    my @removed = @{$removed_ref};
    my @message;

    push(@message, "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 TRANSITIONAL//EN\">\n");
    push(@message, "<html>\n");
    push(@message, "<head>\n");
    push(@message, "<title>The XSD Files In the CodeX Repository Have Changed</title>\n");
    push(@message, "<meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\">\n");
    push(@message, "</head>\n");
    push(@message, "<body>\n");
    push(@message, "<p>\n");
    push(@message, "The set of XSD files in the CodeX repository has changed.\n");
    push(@message, "This message contains a list of the changes (the\n");
    push(@message, "full list is available as an attachment).\n");
    push(@message, "The schema files are grouped by project, and both project\n");
    push(@message, "names and new entries contain hyperlinks into CodeX.\n");
    push(@message, "</p>\n");

    if ($#added >= 0) {
	push(@message, "<h1>Schemas Added</h1>\n");
	foreach (@{compose_file_list("h2", 1, \@added)}) {
	    push(@message, $_);
	}
    }

    if ($#removed >= 0) {
	push(@message, "<h1>Schemas Removed</h1>\n");
	foreach (@{compose_file_list("h2", 0, \@removed)}) {
	    push(@message, $_);
	}
    }
    push(@message, "</body>\n");
    push(@message, "</html>\n");

    \@message;
}

#
# Compose the message to a project admin.  Return a reference to a list of lines.
#
sub compose_project_email {
    my ($project, $paths_ref) = @_;
    my @message;

    push(@message, "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 TRANSITIONAL//EN\">\n");
    push(@message, "<html>\n");
    push(@message, "<head>\n");
    push(@message, "<title>CodeX project ${project}: Information on the XML Schema Registry</title>\n");
    push(@message, "<meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\">\n");
    push(@message, "</head>\n");
    push(@message, "<body>\n");

    open MESSAGE, "<$message_file"
	|| die "Could not open message text file: $message_file";

    while (<MESSAGE>) {
	push(@message, $_);
    }

    push(@message, "<h1>List of Schema files</h1>\n");
    foreach (@{compose_file_list("h2", 1, $paths_ref)}) {
	push(@message, $_);
    }

    push(@message, "</body>\n");
    push(@message, "</html>\n");

    \@message;
}

#
# Send email to project admins
#
sub send_project_email {
    my ($from, $project, $email_addrs_ref, $paths_ref) = @_;

    my $smtp = Net::SMTP->new($mailhost);
    unless ($smtp) {
    	print "Could not open a SMTP connection.\n";
	return 0;
    }

    my @email_addrs;
    if ($override_address) {
	@email_addrs = ($override_address);
    }
    else {
	@email_addrs = @{$email_addrs_ref};
    }

    my $requested = join(", ", @{$email_addrs_ref});
    my $addrs = join(", ", @email_addrs);

    if ($debug) {
	print "DBG: Sending project email\n";
	print "DBG: From: ", $from, "\n";
	print "DBG: To (requested): ", $requested, "\n";
	print "DBG: To (actual): ", $addrs, "\n";
    }

    $smtp->mail($from);
    $smtp->to(@email_addrs);

    $smtp->data();

    $smtp->datasend("To: " . $requested . "\n");
    $smtp->datasend("Subject: CodeX project ${project}: Information on the XML Schema Registry\n");
    $smtp->datasend("From: ${from}\n");
    $smtp->datasend("Content-type: text/html\n");
    $smtp->datasend("\n");

    foreach (@{compose_project_email($project, $paths_ref)}) {
	$smtp->datasend($_);
    }

    $smtp->dataend();

    $smtp->quit;

    return 1;
}

#
# Return true if a string is a path name (as opposed to a project name)
#
sub is_path {
    my ($path) = @_;
    $path =~ m@^/@;
}

#
# Fudge a time value by increasing it a random amount up to $fudge_max.
# The purpose is to avoid having many emails repeat at exactly the same
# time
#
sub fudge_time {
    my ($value) = @_;
    my $fudged = int(0.5 + $value + rand($fudge_max));

    if ($debug) {
	print "DBG: fudging time ", $value, " to ", $fudged, "\n";
    }
    
    $fudged
}

#
# Import the database from a text file
#
sub import_database {
    my ($filename) = @_;

    open IMPORT, $filename || die "Cannot import from $filename";

    while (<IMPORT>) {
	chop;
	m|([0-9]+):(.*)|;
	$info{$2} = $1;
    }

    close IMPORT;
}

#
# Export the database to a text file
#
sub export_database {
    my ($filename) = @_;

    open EXPORT, ">$filename" || die "Cannot export to $filename";
    
    while (my ($key, $value) = each(%info)) {
	print EXPORT $value, ":", $key, "\n";
    }

    close EXPORT;
}


###################################################################

#
# Get the old list of files and email message times.
# Handle --import and --export here.
#

sysopen(LOCKFILE, $schema_lock_file, O_CREAT|O_RDWR)
    || die "Could not open lock file: $schema_lock_file";

flock(LOCKFILE, LOCK_EX|LOCK_NB)
    || die "Could not get lock on lock file: $schema_lock_file";

$DB = tie(%info, 'DB_File', $schema_db_file, O_CREAT|O_RDWR|($import?O_TRUNC:0), 0600);
unless ($DB) {
    die "Could not open database: $schema_db_file";
}

if ($import) {       # Cheat... read the database from a text file
    import_database($import);
    untie %info;
    exit 0;
}

if ($debug > 1) {
    print "DBG: Dumping database\n";
    while (my ($key, $value) = each(%info)) {
	my $type = (is_path($key)? "PATH" : "PROJ");
	print "DBG: $type $key => $value\n";
    }
}

if ($export) {       # Don't actually do anything, just write the DB to a text file.
    export_database($export);
    exit 0;
}

# 
# The "find_targets" routine walks over all suitable files and
# calls a function for each.  Collect all files, find out which
# ones are new, and group files into projects.
#

my %newmap;
my @added;
my %project_files;

find_targets($cvs_prefix, sub {
    my ($file) = @_;
    my ($project,$path) = decompose_cvs_path($file);

    $newmap{$file} = $current_time;
    unless ($info{$file}) {
	push(@added, $file);
    }
    my $proj_list = $project_files{$project};
    if (! $proj_list) {
	$project_files{$project} = [ $file ];
    }
    else {
	push(@$proj_list, $file);
    }
})
    || die "Could not find schema files in $cvs_prefix";

#
# Now, figure out which files have changed, and which files are associated with each project
#

my %project_dates;
my @removed;

while (my ($key, $value) = each(%info)) {
    if (is_path($key)) {
	unless ($newmap{$key}) {
	    push(@removed, $key);
	}
    }
    else {
	$project_dates{$key} = $value;
    }
}

@added = sort(@added);
@removed = sort(@removed);

if ($debug) {
    foreach (@added) {
	print "Added ", $_, "\n";
    }
    foreach (@removed) {
	print "Removed ", $_, "\n";
    }
}

#
# For each project, find when it was last updated, and if it was too long
# ago send an email to the project admins.
#

my $exit_code = 0;

while (my ($project, $files_ref) = each(%project_files)) {
    my $date = $project_dates{$project};
    my $delta = $current_time - $date;

    if ($debug) {
	print "Project $project, date=$date, delta=$delta\n";
    }

    next if $date && (($current_time - $date) < fudge_time($resend_interval));

    my ($query, $c, $res);

    $query = "SELECT user.email FROM user, groups, user_group "
	. "WHERE groups.unix_group_name LIKE " . $dbh->quote($project)
	. "&& user_group.group_id = groups.group_id "
	. "&& user.user_id = user_group.user_id "
	. "&& user_group.admin_flags != '';"
	;

    $c = $dbh->prepare($query);
    $res = $c->execute();

    if (! $res || ($c->rows < 1)) {
	print "Could not find any email addresses for $project.\n";
	exit_code = 1;
    }
    else {
	my @row;
	my @list;
	while (@row = $c->fetchrow_array) {
	    push(@list, $row[0]);
	}
	my @files;

	$project_dates{$project} = $current_time;

	send_project_email($from_address, $project, \@list, $project_files{$project});

	$info{$project} = $current_time;
	$DB->sync();
    }
}

#
# If there are any new files, or if any have been removed, generate an email.
#

if ($#added >= 0 || $#removed >= 0) {
    if ($admin_address) {
	my $intro = intro_message(\@added, \@removed);

	my @all_files = sort(keys(%newmap));
	unless (send_admin_email($from_address, $admin_address, $mail_subject, $intro, \@all_files)) {
	    print "Could not send notification mail to ", $admin_address, ".\n";
	    $exit_code = 1;
	}
    }

    foreach my $file (@added) {
	$info{$file} = $current_time;
    }

    foreach my $file (@removed) {
	delete $info{$file};
    }
}

untie %info;

exit($exit_code);
