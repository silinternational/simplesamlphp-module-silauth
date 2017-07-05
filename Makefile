
# Set up the default (i.e. - first) make entry.
start: web

bash:
	docker-compose run --rm web bash

bashtests:
	docker-compose run --rm tests bash

behat:
	docker-compose run --rm tests bash -c "vendor/bin/behat --config=features/behat.yml --strict --stop-on-failure"

behatappend:
	docker-compose run --rm tests bash -c "vendor/bin/behat --config=features/behat.yml --strict --append-snippets"

behatv:
	docker-compose run --rm tests bash -c "vendor/bin/behat --config=features/behat.yml --strict --stop-on-failure -v"

bounce:
	docker-compose up -d web

clean:
	docker-compose kill
	docker system prune -f

composer:
	docker-compose run --rm tests bash -c "composer install --no-scripts"

composerrequire:
	docker-compose run --rm tests bash -c "composer require $(NAME) --no-scripts"
# Example: `make composerrequire NAME=monolog/monolog`

composerupdate:
	docker-compose run --rm tests bash -c "composer update --no-scripts"

db:
	docker-compose up -d db

enabledebug:
	docker-compose exec web bash -c "/data/enable-debug.sh"

generatemodels: migratedb
	docker-compose run --rm web bash -c "/data/symlink.sh && /data/src/rebuildbasemodels.sh"

migratedb: db
	docker-compose run --rm web bash -c "/data/symlink.sh && whenavail db 3306 60 /data/src/yii migrate --interactive=0"

migratetestdb: testdb
	docker-compose run --rm tests bash -c "whenavail testdb 3306 60 /data/src/yii migrate --interactive=0"

migration:
	docker-compose run --rm web bash -c "/data/symlink.sh && /data/src/yii migrate/create $(NAME)"

phpunit:
	docker-compose run --rm tests bash -c "cd src/tests && ../../vendor/bin/phpunit ."

ps:
	docker-compose ps

rmdb:
	docker-compose kill db
	docker-compose rm -f db

rmtestdb:
	docker-compose kill testdb
	docker-compose rm -f testdb

test: composer rmtestdb testdb migratetestdb behat phpunit

testdb:
	docker-compose up -d testdb

web: db migratedb
	docker-compose up -d web
