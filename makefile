clean-test-project:
	rm -rf test-project
test-project: clean-test-project
	composer create-project --repository-url=./packages.json apie/apie-project-starter test-project
