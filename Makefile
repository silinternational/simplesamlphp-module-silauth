
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

ldap:
	docker-compose up -d ldap

ldapadmin: ldap
	docker-compose up -d ldapadmin

ldapload: ldap
	docker-compose run --rm ldapload

migratedb: db
	docker-compose run --rm web bash -c "whenavail db 3306 60 /data/src/yii migrate --interactive=0"

migratetestdb: testdb
	docker-compose run --rm web bash -c "MYSQL_HOST=testdb MYSQL_DATABASE=test whenavail testdb 3306 60 /data/src/yii migrate --interactive=0"

migration:
	docker-compose run --rm web bash -c "/data/src/yii migrate/create $(NAME)"

phpunit:
	docker-compose run --rm phpunit

ps:
	docker-compose ps

rmdb:
	docker-compose kill db
	docker-compose rm -f db

rmldap:
	docker-compose kill ldap
	docker-compose rm -f ldap

rmtestdb:
	docker-compose kill testdb
	docker-compose rm -f testdb

start: web

test: composer rmtestdb rmldap testdb ldap migratetestdb ldapload behat phpunit

testdb:
	docker-compose up -d testdb

web: db composer migratedb
	docker-compose up -d web
