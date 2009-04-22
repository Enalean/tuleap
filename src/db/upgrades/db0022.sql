# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the
# CodeX source code. All references are included
# below.
#
# Description
# Correct the comment type for follow-up comments in the artifact_history that
# have been set by error on NULL instead of 100 (='None') (possible since
# commit #11074 on trunk and branch CX_2_2_SUP and commit #12656 on branch
# CX_2_0_SUP)
#
# Please apply this patch no matter whether you are on branch CX_2_0_SUP, 
# CX_2_2_SUP or on the trunk
#
#
# References:
# See SR 135 on partners
#
# Dependencies:
# none
#
#

UPDATE artifact_history SET type = '100' WHERE field_name = 'details' AND type is NULL;