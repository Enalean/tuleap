#!/usr/bin/perl

#
# Trigger huson
#
# params:
# 1) group_id (int) group_id of the projet that host the scm repository
# 2) scm (string) scm use for the commit (accepted values are 'svn' and 'cvs')
#
sub trigger_hudson_builds() {
    
    my $group_id = shift(@_);
    my $scm = shift(@_);
    
    my ($query_plugin, $c_plugin, $res_plugin);
    $query_plugin = "SELECT * FROM plugin WHERE name='hudson' AND available=1";
    $c_plugin = $dbh->prepare($query_plugin);
    $res_plugin = $c_plugin->execute();
    if ($res_plugin && ($c_plugin->rows > 0)) {
        
        # If hudson plugin is not installed and enabled, don't trigger anything
        my ($query, $c, $res);
        $query = "SELECT * FROM plugin_hudson_job WHERE group_id='$group_id' AND use_".$scm."_trigger=1";
        $c = $dbh->prepare($query);
        $res = $c->execute();
        if ($res && ($c->rows > 0)) {
          # Use Codendi HTTP API
          my $ua = LWP::UserAgent->new;
          $ua->agent('Codendi CI Perl Agent');
          $ua->timeout(10);
    
          while ($trigger_row = $c->fetchrow_hashref) {
            my $job_url = $trigger_row->{'job_url'};
            my $token = $trigger_row->{'token'};
            my $token_url = '';
            if ($token ne '') {
              $token_url = "?token=$token";
            }
            my $req = POST "$job_url/build$token_url";
            my $response = $ua->request($req);
            
            if ($response->is_success) {
            } else {
              if ($response->code eq 302) {
                # 302 is the http code for redirect (http response for Hudson build)
                # so we consider it as a success
              } else {
                my $logfile = "$codendi_log/hudson_log";
                my $statusline = $response->status_line;
                if (open(LOGFILE, ">> $logfile")) {
                  print LOGFILE "Hudson build error with build url $job_url/build$token_url : $statusline \n";
                  close LOGFILE;
                }
              }
            }
          }
        }
        
    }
}

1;
