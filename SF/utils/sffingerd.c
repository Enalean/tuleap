
/* 
   sffingerd.c - SourceForge's crazy little fingerd
   do-anything-you-want-with-it-don't-blame-me-for-anything license.
   
   Mukund <muks@users.sourceforge.net>
   6th July, 2000
   
   #undef LOG_REQUESTS if you don't wanna log any user requests.
   
   gcc -s -I/path/to/mysql/include -o sffingerd sffingerd.c -L/path/to/mysql/lib/mysql -lmysqlclient
   cp -f sffingerd /usr/local/sbin

   MUST BE RUN UNDER inetd
   finger stream tcp nowait nobody /usr/local/sbin/sffingerd fingerd
   
*/

#define LOG_REQUESTS

#define MYSQL_HOST	"localhost"              // EDIT THIS
#define MYSQL_DATABASE	"sourceforge"
#define MYSQL_USER	"root"
#define MYSQL_PASSWORD	""

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <syslog.h>
#include <time.h>
#include <unistd.h>
#include <sys/types.h>
#include <mysql.h>

#ifdef	LOG_REQUESTS
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#endif

int main(int argc, char *argv[])
{
#ifdef	LOG_REQUESTS
	struct sockaddr_in	name;
	socklen_t		length;
	unsigned long int	remote;
	char			*remote_ip;
	struct hostent		*host;
	char			remote_machine[1024];
#endif
	int			l, count;
	char			user[256];
	MYSQL			mysql, *sock;
	MYSQL_RES		*user_result, *group_result;
	MYSQL_ROW		user_row, group_row;
	char			qbuf[4096];
	time_t			addtime;
	
	openlog("sffingerd", (LOG_CONS | LOG_PID), LOG_DAEMON);

	if (geteuid() == 0)
	{
		syslog(LOG_ERR, "can't run sffingerd as root");
		fprintf(stderr, "can't run sffingerd as root\n");
		return 1;
	}

#ifdef	LOG_REQUESTS
	
	length = sizeof(name);
	if (getpeername(0, (struct sockaddr *) &name, &length) == -1)
	{
		perror("getpeername");
		syslog(LOG_ERR, "getpeername: %m");
		closelog();
		return 0;
	}
	
	if (name.sin_family != AF_INET)
	{
		syslog(LOG_ERR, "connection not from INET family");
		fprintf(stderr, "connection not from INET family\n");
		closelog();
		return 0;
	}
	
	remote = ntohl(name.sin_addr.s_addr);
	remote_ip = inet_ntoa(name.sin_addr);
	
	if ((host = gethostbyaddr((char *) &name.sin_addr,
			sizeof(struct in_addr), AF_INET)) != NULL)
		snprintf(remote_machine, 1023, "%.512s [%.15s]", host->h_name, remote_ip);
	else
		snprintf(remote_machine, 1023, "%.15s [%.15s]", remote_ip, remote_ip);
#endif

	// get the query string - i.e., the username

	if (fgets(user, 255, stdin) == NULL)
	{
		syslog(LOG_ERR, "fgets failed - no input string?");
		fprintf(stdout, "fgets failed - no input string?\n");
		closelog();
		return 1;
	}

	// strip out the \n and \r at the end of the username
	// in the query and terminate the string there
	
	for (l = 0; user[l]; l++)
	{
		if ((user[l] == '\r') || (user[l] == '\n'))
		{
			user[l] = '\0';
			break;
		}
	}

#ifdef	LOG_REQUESTS
	syslog(LOG_INFO, "finger request from host %s for user '%s'", remote_machine, user);
#endif

	fprintf(stdout, "\nWelcome to SourceForge's Finger Service!\n"
			"----------------------------------------\n\n");
	
	// check for valid sourceforge username
	// taken from www/include/account.php -> account_namevalid()
	
	if ((strchr(user, ' ') != NULL) ||
		(strspn(user, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_") != strlen(user)) ||
		(strlen(user) < 3) || (strlen(user) > 15))
	{
		fprintf(stdout, "You have supplied a malformed username for a SourceForge developer.\n"
				"Please try again with a legal username. Thank you.\n\n\n");
		closelog();
		return 0;
	}

	// hmm. fine. now connect to the mysql database
	// and check if the user exists.
	
	mysql_init(&mysql);
	
	if ((sock = mysql_real_connect(&mysql, MYSQL_HOST,
		MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE, 0, NULL, 0)) == NULL)
	{
		syslog(LOG_ERR, "unable to connect to the MYSQL database");
		fprintf(stderr, "unable to connect to the MYSQL database\n");
		closelog();
		return 1;
	}
	
	snprintf(qbuf, 4095, "SELECT user_id, user_name, realname, people_view_skills, people_resume, add_date FROM user WHERE user_name='%s'", user);
	
	if (mysql_query(sock, qbuf) != 0)
	{
		syslog(LOG_ERR, "MYSQL SELECT FROM user query failed [%s]", mysql_error(sock));
		fprintf(stderr, "MYSQL SELECT FROM user query failed [%s]\n", mysql_error(sock));
		mysql_close(sock);
		closelog();
		return 1;
	}
	
	if ((user_result = mysql_store_result(sock)) == NULL)
	{
		syslog(LOG_ERR, "mysql_store_result() failed [%s]", mysql_error(sock));
		fprintf(stderr, "mysql_store_result() failed [%s]\n", mysql_error(sock));
		mysql_close(sock);
		closelog();
		return 1;
	}
	
	if ((count = mysql_num_rows(user_result)) > 1)
	{
		syslog(LOG_ERR, "mysql_num_rows() for user '%s' returned more than one record!", user);
		fprintf(stderr, "mysql_num_rows() for user '%s' returned more than one record!\n", user);
		mysql_free_result(user_result);
		mysql_close(sock);
		closelog();
		return 1;
	}

	if (count <= 0)
	{
#ifdef	LOG_REQUESTS
		syslog(LOG_ERR, "query for user '%s' from host %s didn't happen. user's unknown here.", user, remote_machine);
#endif
		fprintf(stdout, "\nThe username you supplied is unknown here. That user does not exist.\n");
	}
	else
	{
		user_row = mysql_fetch_row(user_result);
		
		fprintf(stdout, "\nPersonal Information:\n\n");
		fprintf(stdout, "\tUserID   : %s\n\tUsername : %s\n", user_row[0], user_row[1]);
		fprintf(stdout, "\tE-mail   : %s@users.sourceforge.net\n\tRealname : %s\n", user_row[1], user_row[2]);
		
		addtime = atoi(user_row[5]);
		fprintf(stdout, "\nMember on SourceForge since %s", ctime(&addtime));

		snprintf(qbuf, 4095, "SELECT groups.group_name, groups.group_id, groups.unix_group_name "
					"FROM groups, user_group WHERE user_group.user_id = '%s' AND "
					"groups.group_id = user_group.group_id AND "
					"groups.is_public=1", user_row[0]);
	
		if (mysql_query(sock, qbuf) != 0)
		{
			syslog(LOG_ERR, "MYSQL SELECT FROM groups, user_group query failed [%s]", mysql_error(sock));
			fprintf(stderr, "MYSQL SELECT FROM groups, user_group query failed [%s]\n", mysql_error(sock));
			mysql_free_result(user_result);
			mysql_close(sock);
			closelog();
			return 1;
		}
	
		if ((group_result = mysql_store_result(sock)) == NULL)
		{
			syslog(LOG_ERR, "mysql_store_result() failed [%s]", mysql_error(sock));
			fprintf(stderr, "mysql_store_result() failed [%s]\n", mysql_error(sock));
			mysql_free_result(user_result);
			mysql_close(sock);
			closelog();
			return 1;

		}
	
		count = mysql_num_rows(group_result);

		if (count <= 0)
		{
			fprintf(stdout, "\n\nThe user does not belong in any groups.\n");
		}
		else
		{
			fprintf(stdout, "\n\nMember of the following groups:\n\n");

			while ((group_row = mysql_fetch_row(group_result)) != NULL)
				fprintf(stdout, "\to %s (http://sourceforge.net/projects/%s/)\n", group_row[0], group_row[2]);
		}
		
		mysql_free_result(group_result);
		
		if (atoi(user_row[3]) != 1)
			fprintf(stdout, "\n\nThe user has set his/her profile to private.\n");
		else
		{
			fprintf(stdout, "\n\nProfile follows:\n\n");
			fprintf(stdout, "%s\n\n", user_row[4]);
		}
		
	}

	mysql_free_result(user_result);
	mysql_close(sock);
	
	fprintf(stdout, "\n\n---------------------------------------------------------------------\n"
			"For FREE hosting, forums, mailing lists, CVS, shell, db, bug tracking,\n"
			"and more for Open Source projects, come to http://sourceforge.net/\n");
	
	fprintf(stdout, "\n");

	closelog();
	
	return 0;   // darn_compiler();
}
