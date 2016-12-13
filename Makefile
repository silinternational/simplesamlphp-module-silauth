
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

generatemodels: migratedb
	docker-compose run --rm web bash -c "/data/src/rebuildbasemodels.sh"

migratedb: db
	docker-compose run --rm web bash -c "whenavail db 3306 60 /data/src/yii migrate --interactive=0"

migratetestdb: testdb
	docker-compose run --rm web bash -c "MYSQL_HOST=testdb MYSQL_DATABASE=test whenavail testdb 3306 60 /data/src/yii migrate --interactive=0"

migration:
	docker-compose run --rm web bash -c "/data/src/yii migrate/create $(NAME)"

phpunit:
	docker-compose run --rm web bash -c "cd src/tests && MYSQL_HOST=testdb MYSQL_DATABASE=test ../../vendor/bin/phpunit ."

ps:
	docker-compose ps

rmdb:
	docker-compose kill db
	docker-compose rm -f db

rmtestdb:
	docker-compose kill testdb
	docker-compose rm -f testdb

start: web

test: composer rmtestdb testdb migratetestdb behat phpunit

testdb:
	docker-compose up -d testdb

web: db composer migratedb
	docker-compose up -d web
