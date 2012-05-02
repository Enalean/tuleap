default:
	@echo "possible targets: 'doc' 'test'"

doc:
	$(MAKE) -C documentation all

test:
	@php -d allow_call_time_pass_reference=On tests/bin/simpletest tests/simpletest plugins
