#!/usr/bin/python
# -*-python-*-
#
# Copyright (C) 1999-2006 The ViewCVS Group. All Rights Reserved.
# Copyright (c) Enalean, 2016. All rights reserved
#
# By using this file, you agree to the terms and conditions set forth in
# the LICENSE.html file which can be found at the top level of the ViewVC
# distribution or at http://viewvc.org/license-1.html.
#
# For more information, visit http://viewvc.org/
#
# -----------------------------------------------------------------------
#
# viewvc: View CVS/SVN repositories via a web browser
#
# -----------------------------------------------------------------------
#
# This is a teeny stub to launch the main ViewVC app. It checks the load
# average, then loads the (precompiled) viewvc.py file and runs it.
#
# -----------------------------------------------------------------------
#

#########################################################################
#
# INSTALL-TIME CONFIGURATION
#
# These values will be set during the installation process. During
# development, they will remain None.
#

LIBRARY_DIR = r"/usr/share/viewvc/lib"
CONF_PATHNAME = r"/etc/tuleap/conf/viewvc.conf"

#########################################################################
#
# Adjust sys.path to include our library directory
#

import sys
import os

if LIBRARY_DIR:
  sys.path.insert(0, LIBRARY_DIR)
else:
  sys.path.insert(0, os.path.abspath(os.path.join(sys.argv[0],
                                                  "../../../lib")))

#########################################################################

### add code for checking the load average

#########################################################################

# go do the work
import sapi
import viewvc

server = sapi.CgiServer()
cfg = viewvc.load_config(CONF_PATHNAME, server)

# Inject path root
repo_name = os.getenv('TULEAP_REPO_NAME','')
repo_path = os.getenv('TULEAP_REPO_PATH','')
cfg.general.svn_roots[repo_name] = repo_path

viewvc.main(server, cfg)
