UPDATE groups SET http_domain = REPLACE(http_domain, 'yourdev.rd.francetelecom.fr', 'orangeforge.rd.francetelecom.fr');
UPDATE service SET link = REPLACE(link,'yourdev.rd.francetelecom.fr','orangeforge.rd.francetelecom.fr') WHERE short_name = 'homepage';
UPDATE service SET link = REPLACE(link,'https://', 'http://') WHERE short_name = 'homepage' AND link LIKE '%orangeforge.rd.francetelecom.fr%';
