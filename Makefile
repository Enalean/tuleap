AUTOLOAD_EXCLUDES=mediawiki|tests|template
LESS_PATH=plugins src

default:
	@echo "possible targets: 'doc' 'test' 'autoload' 'less' 'less-dev' 'api_test' 'api_test_group'"

doc:
	$(MAKE) -C documentation all

test:
	@php tests/bin/simpletest tests/simpletest plugins

autoload:
	@echo "Generate core"
	@(cd src/common; phpab  --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
	     echo "Generate $$path"; \
	     (cd "plugins/$$path/include"; phpab --compat -o autoload.php .) \
        done;

less:
	@find $(LESS_PATH) -type f -name "*.less" | while read -r file; do \
		echo "Compiling $$file"; \
		filename=$$(basename "$$file"); \
		filename="$${filename%.*}"; \
		path=$$(dirname "$$file"); \
		# Comments are striped by plessc from css files but we need to keep the license comment at the top of the file \
		head -n 100 $$file | grep -iP '^/\*\*(\n|.)*?copyright(\n|.)*?\n\s?\*/' > "$$path/$$filename.css"; \
		# Append the compiled css after the license comment \
		plessc "$$path/$$filename.less" >> "$$path/$$filename.css"; \
	done;

less-dev:
	@tools/utils/less-dev.sh `pwd`

api_test_setup:
	cp tests/rest/bin/composer.json .
	curl -sS https://getcomposer.org/installer | php
	php composer.phar install
	cp tests/rest/bin/integration_tests.inc.dist /etc/codendi/conf/integration_tests.inc
	cp tests/rest/bin/dbtest.inc.dist /etc/codendi/conf/dbtest.inc

api_test:
	src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php --bootstrap tests/lib/rest/bootstrap.php tests/rest

api_test_group:
	src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php --group $g tests/rest
