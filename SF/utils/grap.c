/* graplite - General execution wRAPper LITE!
 * Copyright (C) 1999 Lion Templin <lion@leonine.com>
 * FILE: graplite.c
 * VERSION : 0.1 (991111)
 *
 * $Id$
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * GOTO FOREVER!
 *
 *	Coded on Northwest Airlines Flight 1065, CHI to MSP
 *
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

/* grap is a wrapper designed to verify commands before passing them to system()
   or just reporting the accepted command.  grap will report an error if the
   input is invalid.  It checks for string lengths (prevent overflows),
   specific sets of options and commands.
   
   grap, in full force, is called as:  <grap> <option> "<command> <arguments
   list ... >" Where <grap> is this program, <option> is an optional trap
   for a single option (like "-e" or "-c" used by programs that call shells,
   subject to the approval list below), <command> is the command wished to
   be run (subject to the approval list below), and <arguments list .. > is
   the list of args passed to <command>.  All are optional, allowing for
   forms such as:
   	graplite -e "foo"   graplite "foo bar"  graplite -e "foo -c foo -f bar"
   	<g     ><o ><cmd>   <g     > <cmd/args> <g     ><o> <cmd/  args       >
   	
	<options> and <command> need to be exact matched to those in the
	acceptance list.  
*/

/* Define the locations of <option> <command> and <arguements list .. >
   on the command line.  0 is this program, begin at 1.  Note that 
   ARGS_ARGC takes everything FROM that position to the end of the
   arguments.

   Undefine any of these to not use them.
*/

#define	OPTION_ARGC		1
#define ARGS_ARGC		2

#define	ARGS_ARE_SINGLE_STRING

/* Define how the <arguements list .. > is checked.
   define ARGS_ALNUMOK for A-Za-z0-9 to be OK
   define any other chars in the string ARGS_CHAROK

   Turn both these off to accept everything.
   WARNING, might be able to bad things with
   shell special chars such as & ; , etc.
*/

#define MAXSTRLEN		256		/* maximum single string length
						   (no max on final command) */
/* Define what strings are acceptable in <option> */
char *options[] = 		{ "-c", "-e", NULL };

/* Define what strings are acceptable in <command>
   define an optional execution path CMD_PATH if desired */
char *commands[] = 		{ "cvs", "server", NULL };

#define MAXARGS		256

/* NO USER SERVICEABLE PARTS BELOW --------------------------------- */


#define	GRAP_TRUE		1
#define GRAP_FALSE		0
#define CMD_POS			0

int main(int argc, char *argv[]) {

	int i, j, n, argslen, flag;
	char *buf;
	char **args[MAXARGS];


	if(argc < 3) {
			/* printf("FATAL: %s bailed because not enough options.\n", argv[0]); */

			printf("\nWelcome to cvs1.sourceforge.net\n\n");
			printf("This is a restricted Shell Account\n");
			printf("You cannot execute anything here.\n\n");

			exit(1);
	}
	

	/* process the initial option (see options array) */

	i = -1;
	while((options[++i] != NULL) && strncmp(options[i], argv[OPTION_ARGC], MAXSTRLEN));
		if(options[i] == NULL || strlen(argv[OPTION_ARGC]) > MAXSTRLEN) {
			/* printf("FATAL: %s bailed because options didn't qualify.\n", argv[0]); */

			printf("\nWelcome to cvs1.sourceforge.net\n\n");
			printf("This is a restricted Shell Account\n");
			printf("You cannot execute anything here.\n\n");

			exit(1);
		}
	
	/* break single command and args string into seperate strings
	   in a char** for execvp() to use */

	i = 0;
	flag = GRAP_TRUE;
	buf = argv[ARGS_ARGC];

	j = CMD_POS;
	n = 0;

	while(buf[i] != NULL && j < MAXARGS) {
		if(buf[i] == ' ') {
			buf[i] = NULL;
			flag = GRAP_TRUE;
		} else 
			if(flag) {
				args[j++] = &buf[i];
				flag = GRAP_FALSE;
				args[j] = NULL;
				n++;
			}
		i++;
	}

	/* check the command to insure it's in the acceptance list */

	i = -1;
	while((options[++i] != NULL) && strncmp(commands[i], args[CMD_POS], MAXSTRLEN));
	if(options[i] == NULL || strlen(args[CMD_POS]) > MAXSTRLEN) {

	/* 	printf("FATAL: %s bailed because command didn't qualify.\n", args[CMD_POS]); */

		printf("\nWelcome to cvs1.sourceforge.net\n\n");
		printf("This is a restricted Shell Account\n");
		printf("You cannot execute anything here.\n\n");

		exit(1);
	}


	/* ok, the command is clear, exec() it */

	execvp(args[CMD_POS], args);

}

