.PHONY: install update phpcs phpcbf php80compatibility tests testdox ci

PHP_FILES := $(shell find src tests -type f -name '*.php')

install:
	composer install

update:
	composer update

composer.lock: composer.json
	composer install

vendor/bin/phpunit: composer.lock

build/reports/cs/eureka.xml: composer.lock $(PHP_FILES)
	mkdir -p build/reports/cs
	./vendor/bin/phpcs --standard=./config/phpcs/eureka.xml --cache=./build/cs_eureka.cache -p --report-full --report-checkstyle=./build/reports/cs/eureka.xml src/ tests/

build/reports/cs/php80compatibility.xml: composer.lock $(PHP_FILES) config/phpcs/php8.0_compatibility.xml
	mkdir -p build/reports/cs
	./vendor/bin/phpcs --standard=./config/phpcs/php8.0_compatibility.xml --cache=./build/cs_php80compatibility.cache -p --report-full --report-checkstyle=./build/reports/cs/php80compatibility.xml

phpcs: build/reports/cs/eureka.xml

phpcbf: composer.lock
	./vendor/bin/phpcbf --standard=./config/phpcs/eureka.xml src/ tests/

php80compatibility: build/reports/cs/php80compatibility.xml

testdox: vendor/bin/phpunit $(PHP_FILES)
	php -dzend_extension=xdebug.so ./vendor/bin/phpunit -c ./phpunit.xml.dist --fail-on-warning --testdox

build/reports/phpunit/unit.xml build/reports/phpunit/unit.cov: vendor/bin/phpunit $(PHP_FILES)
	mkdir -p build/reports/phpunit
	XDEBUG_MODE=coverage php -dzend_extension=xdebug.so ./vendor/bin/phpunit -c ./phpunit.xml.dist --coverage-clover=./build/reports/phpunit/clover.xml --log-junit=./build/reports/phpunit/unit.xml --coverage-php=./build/reports/phpunit/unit.cov --coverage-html=./build/reports/coverage/ --fail-on-warning

tests: build/reports/phpunit/unit.xml build/reports/phpunit/unit.cov

cleanup:
	if [ "$(ls -A ./build)"  ]; then rm -r ./build/*; fi

ci: cleanup install phpcs tests php80compatibility
