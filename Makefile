AUTOLOADED_PATH=src/common/PFO plugins/agiledashboard/include plugins/cardwall/include plugins/fulltextsearch/include plugins/tracker/include src/common/project

default:
	@echo "possible targets: 'doc' 'test' 'autoload'"

doc:
	$(MAKE) -C documentation all

test:
	@php -d allow_call_time_pass_reference=On tests/bin/simpletest tests/simpletest plugins

autoload:
	@for path in $(AUTOLOADED_PATH); do \
	     echo "Generate $$path"; \
	     (cd "$$path"; phpab --compat -o autoload.php .) \
        done;
