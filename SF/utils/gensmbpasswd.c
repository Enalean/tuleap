/*
 CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
 Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
 http://codex.xerox.com

 $Id$

  License:
    This file is subject to the terms and conditions of the GNU General Public
    license. See the file COPYING in the main directory of this archive for
    more details.

 Purpose:
    This utility takes a windows password (in clear text) as its sole argument
    and return two forms of the Windows encrypted passwords . The first one 
    if the Windows 2K form and the 2nd one is the NT compatible encryption

    It is derived from Samba smbpasswd utility. To compile it you must untar
    the Samba source tree (from the RPM source file unarchived in
    /usr/src/redhat/BUILD. Run configure. Then apply the SambaMakefile.patch
   to the generated makefile. Run make. Then in the bin directory you'll find the
   gensmbpasswd binary. Copy it in /usr/local/bin with permission 755

 Author: Laurent Julliard <Laurent.Julliard@xrce.xerox.com
*/


#include "includes.h"

main(int argc, char **argv)
{
  uchar p16[16];
  uchar nt_p16[16];
  char ascii_p16[32+1];
  char ascii_nt_p16[32+1];
  char passwd[130];
  int i;
  struct smb_passwd smb_pwent;
  char *smbpasswd_entry;

  TimeInit();
	
  setup_logging("gensmbpasswd", True);
  
  charset_initialise();
	
  safe_strcpy(passwd,argv[1],sizeof(passwd)-1);

#ifdef DEBUG_GENPASSWD
  printf("Password is %s\n",passwd);
#endif

  /* Calculate the MD4 hash (NT compatible) of the new password. */
  nt_lm_owf_gen(passwd, nt_p16, p16);

  for( i = 0; i < 16; i++) {
    slprintf((char *)&ascii_p16[i*2], 3, "%02X", p16[i]);
    slprintf((char *)&ascii_nt_p16[i*2], 3, "%02X", nt_p16[i]);
  }

  printf("%s:%s\n",ascii_p16,ascii_nt_p16);

/*
  smb_pwent.smb_userid = 9999;
  smb_pwent.smb_name = "wedonotcare"; 
  smb_pwent.smb_passwd = p16;
  smb_pwent.smb_nt_passwd = nt_p16;
  smb_pwent.pass_last_set_time = time(NULL);
  smb_pwent.acct_ctrl = ACB_NORMAL;
	
  smbpasswd_entry = format_new_smbpasswd_entry(&smb_pwent);
  
  printf("%s\n",smbpasswd_entry);
*/

  exit(0);
  
}
