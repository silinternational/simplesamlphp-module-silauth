
bash:
	docker-compose run --rm web bash

bounce:
	docker-compose up -d web

clean:
	docker-compose kill
	docker-compose rm -f

composer:
	docker-compose run --rm web bash -c "composer install --no-plugins --no-scripts --prefer-dist"

composerupdate:
	docker-compose run --rm web bash -c "composer update --no-plugins --no-scripts --prefer-dist"

db:
	docker-compose up -d db

migratedb: db
	docker-compose run --rm web bash -c "whenavail db 3306 30 vendor/bin/phinx migrate -e development"

migratetestdb: testdb
	docker-compose run --rm web bash -c "whenavail testdb 3306 30 vendor/bin/phinx migrate -e testing"

phpunit:
	docker-compose run --rm web bash -c "cd tests && ../vendor/bin/phpunit ."

ps:
	docker-compose ps

rmdb:
	docker-compose kill db
	docker-compose rm -f db

rmtestdb:
	docker-compose kill testdb
	docker-compose rm -f testdb

start: web

test: composer rmtestdb testdb migratetestdb phpunit

testdb:
	docker-compose up -d testdb

web: db composer dbmigrate
	docker-compose up -d web
