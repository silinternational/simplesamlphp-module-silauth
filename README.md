# simplesamlphp-module-silauth
SimpleSAMLphp auth module implementing custom business logic and password 
migration from LDAP to DB.

## Database Migrations
To create another database migration file, run the following (replacing 
`YourMigrationName` with whatever you want the migration to be named, using 
CamelCase):

    make migration NAME=YourMigrationName
