default:
	@echo "possible targets: 'doc'"

doc:
	$(MAKE) -C documentation/user_guide all
