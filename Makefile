UNIT_TEST_TARGETS=$(shell find 'Tests/Unit' -name '*Test.php' -print )
FUNCTIONAL_TEST_TARGETS=$(shell find 'Tests/Functional' -name '*Test.php' -print )

MYSQL_USER="`sed -n 's/user=//p' ~/.my.cnf 2>/dev/null || echo -n 'root'`"
MYSQL_PASS="`sed -n 's/password=//p' ~/.my.cnf 2>/dev/null || echo -n ''`"

PHPUNIT=TYPO3_PATH_ROOT=$(CURDIR)/.build/web .build/vendor/bin/phpunit
PHPUNIT_FUNCTIONAL=typo3DatabaseName="autoflush_test" typo3DatabaseHost="localhost" typo3DatabaseUsername=$(MYSQL_USER) typo3DatabasePassword=$(MYSQL_PASS) $(PHPUNIT)


test: test-lint test-unit test-functional
.PHONY: test

test-lint:
	find . -name '*.php' ! -path './.build/*' -exec php -d display_errors=stderr -l {} > /dev/null \;


$(UNIT_TEST_TARGETS):: install-test-environment
	$(PHPUNIT) --colors -c .build/vendor/typo3/testing-framework/Resources/Core/Build/UnitTests.xml $@

test-unit: $(UNIT_TEST_TARGETS)
.PHONY: test-unit $(UNIT_TEST_TARGETS)


$(FUNCTIONAL_TEST_TARGETS):: install-test-environment
	$(PHPUNIT_FUNCTIONAL) --colors -c .build/vendor/typo3/testing-framework/Resources/Core/Build/FunctionalTests.xml $@

test-functional: $(FUNCTIONAL_TEST_TARGETS)
.PHONY: test-functional $(FUNCTIONAL_TEST_TARGETS)


install-test-environment: .build/vendor/autoload.php

.build/vendor/autoload.php: composer.json
	rm -rf composer.lock .build/
	composer install
