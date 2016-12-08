
bash:
	docker-compose run --rm web bash

behat:
	docker-compose run --rm web bash -c "MYSQL_HOST=testdb MYSQL_DATABASE=test vendor/bin/behat"

behatappend:
	docker-compose run --rm web bash -c "MYSQL_HOST=testdb MYSQL_DATABASE=test vendor/bin/behat --append-snippets"

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

ps:
	docker-compose ps

rmdb:
	docker-compose kill db
	docker-compose rm -f db

rmtestdb:
	docker-compose kill testdb
	docker-compose rm -f testdb

start: web

test: composer rmtestdb testdb migratetestdb behat

testdb:
	docker-compose up -d testdb

web: db composer migratedb
	docker-compose up -d web
