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
extern pstring global_myname;

main(int argc, char **argv)
{
  uchar p16[16];
  uchar nt_p16[16];
  char ascii_p16[32+1];
  char ascii_nt_p16[32+1];
  char passwd[130];
  int i;
  static pstring servicesf = CONFIGFILE;


  TimeInit();
	
  setup_logging("gensmbpasswd", True);
  
  charset_initialise();

  /* Load global config file */
  if (!lp_load(servicesf,True,False,False)) {
      fprintf(stderr, "Can't load %s - run testparm to debug it\n",
              servicesf);
      exit(1);
  }

  safe_strcpy(passwd,argv[1],sizeof(passwd)-1);

#ifdef DEBUG_GENPASSWD
  printf("Password is %s\n",passwd);
#endif


   /* Initilaize host name and get code page for this host */
  if (!*global_myname) {
     char *p;
     fstrcpy(global_myname, myhostname());
     p = strchr(global_myname, '.' );
     if (p) *p = 0;
  }
  strupper(global_myname);

  codepage_initialise(lp_client_code_page());

  /* Calculate the MD4 hash (NT compatible) of the new password. */
  nt_lm_owf_gen(passwd, nt_p16, p16);

  for( i = 0; i < 16; i++) {
    slprintf((char *)&ascii_p16[i*2], 3, "%02X", p16[i]);
    slprintf((char *)&ascii_nt_p16[i*2], 3, "%02X", nt_p16[i]);
  }

  printf("%s:%s\n",ascii_p16,ascii_nt_p16);

  exit(0);
  
}
