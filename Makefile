default:
	@echo "possible targets: 'doc'"

doc:
	$(MAKE) -C documentation all
