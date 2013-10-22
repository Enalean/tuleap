AUTOLOADED_PATH=src/common/PFO plugins/agiledashboard/include plugins/cardwall/include plugins/fulltextsearch/include plugins/tracker/include plugins/git/include src/common/project plugins/boomerang/include plugins/openid/include
LESS_PATH=plugins src

default:
	@echo "possible targets: 'doc' 'test' 'autoload' 'less'"

doc:
	$(MAKE) -C documentation all

test:
	@php tests/bin/simpletest tests/simpletest plugins

autoload:
	@for path in $(AUTOLOADED_PATH); do \
	     echo "Generate $$path"; \
	     (cd "$$path"; phpab --compat -o autoload.php .) \
        done;

less:
	@find $(LESS_PATH) -type f -name "*.less" | while read -r file; do \
		echo "Compiling $$file"; \
		filename=$$(basename "$$file"); \
		filename="$${filename%.*}"; \
		path=$$(dirname "$$file"); \
		# Comments are striped by plessc from css files but we need to keep the license comment at the top of the file \
		head -n 200 $$file | grep -P '/\*\*(\n|.)*?\n\s?\*/' > "$$path/$$filename.css"; \
		# Append the compiled css after the license comment \
		plessc "$$path/$$filename.less" >> "$$path/$$filename.css"; \
	done;
