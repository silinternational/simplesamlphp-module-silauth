# simplesamlphp-module-silauth
SimpleSAMLphp auth module implementing custom business logic and password 
migration from LDAP to DB.

[![Codeship](https://img.shields.io/codeship/ab32f060-a43b-0134-d104-463a26eaa663.svg?style=flat-square)](https://app.codeship.com/projects/190461)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/silinternational/simplesamlphp-module-silauth.svg?style=flat-square)](https://scrutinizer-ci.com/g/silinternational/simplesamlphp-module-silauth/)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/silinternational/simplesamlphp-module-silauth/develop/LICENSE)

## Database Migrations
To create another database migration file, run the following (replacing 
`YourMigrationName` with whatever you want the migration to be named, using 
CamelCase):

    make migration NAME=YourMigrationName
