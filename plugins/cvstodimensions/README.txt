Pour que le plugin fonctionne correctement, vous devez tout d'abord réaliser les actions suivantes:
* récupérer les 3 rpm suivants pour installer les outils Oracle pour Php:
	- oracle-instantclient-basic-10.2.0.3-1.i386.rpm à recupérer sur http://www.oracle.com/technology/software/tech/oci/instantclient/index.html (Xerox peut le fournir si necessaire)
	- oracle-instantclient-devel-10.2.0.3-1.i386.rpm à recupérer sur http://www.oracle.com/technology/software/tech/oci/instantclient/index.html (Xerox peut le fournir si necessaire)
	- php-oci8-4.3.9-3.codex.i386.rpm à récupérer sur le CD de Codex
* installer les rpm : 
	- rpm -ivh oracle-instantclient-basic-10.2.0.3-1.i386.rpm
	- rpm -ivh oracle-instantclient-devel-10.2.0.3-1.i386.rpm
	- rpm -ivh php-oci8-4.3.9-3.codex.i386.rpm 
 
	Pour être sûr que l'installation s'est faite correctement, on peut tester ceci:
	ldd /usr/lib/php4/oci8.so
       		libclntsh.so.10.1 => /usr/lib/oracle/10.2.0.2/client/lib/libclntsh.so.10.1 (0x00538000)
       		libc.so.6 => /lib/tls/libc.so.6 (0x00111000)
       		libnnz10.so => /usr/lib/oracle/10.2.0.2/client/lib/libnnz10.so (0x074c3000)
		(...)
	Si on a un 'not found' à la place de /usr/lib/oracle... c'est que l'installation ne s'est pas déroulée correctement.

* redémarrer apache avec 'service httpd restart'
* s'assurer que codexadm a bien été rajouté dans la base de données de dimensions utilisée avec le bon rôle (pour lancer le script upload.sh)
* s'assurer que le process dimensions a bien été lancé sur la machine

 
