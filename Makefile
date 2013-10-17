AUTOLOADED_PATH=src/common/PFO plugins/agiledashboard/include plugins/cardwall/include plugins/fulltextsearch/include plugins/tracker/include plugins/git/include src/common/project src/common/user plugins/boomerang/include plugins/openid/include

default:
	@echo "possible targets: 'doc' 'test' 'autoload'"

doc:
	$(MAKE) -C documentation all

test:
	@php tests/bin/simpletest tests/simpletest plugins

autoload:
	@for path in $(AUTOLOADED_PATH); do \
	     echo "Generate $$path"; \
	     (cd "$$path"; phpab --compat -o autoload.php .) \
        done;
