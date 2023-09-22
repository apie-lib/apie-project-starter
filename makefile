clean-test-project:
	rm -rf test-project
test-project: clean-test-project
	COMPOSER_CACHE_DIR=/dev/null composer create-project --repository-url=./packages.json apie/apie-project-starter test-project --no-cache
