Squirrel Connection
===================

![PHPStan](https://img.shields.io/badge/style-level%20max-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/connection.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/connection)  [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/connection.svg)](https://packagist.org/packages/squirrelphp/connection) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

Provides a slimmed down concise interface for the low level database connection (ConnectionInterface), as a more simple replacement to Doctrine DBAL and a more streamlined/opinionated interface compared to pure PDO. It supports MySQL, MariaDB, Sqlite and PostgreSQL and is currently based on PDO, although that is considered an implementation detail.

Much code for the exception handling was taken from Doctrine DBAL. This library is currently only internally used in [squirrelphp/queries](https://github.com/squirrelphp/queries) and should not be used on its own, as the API is not yet stable. Use [squirrelphp/queries](https://github.com/squirrelphp/queries) instead or even better, use [squirrelphp/entities](https://github.com/squirrelphp/entities) / [squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle) instead, as those are more high-level and stable.

A stable version of this package will eventually come out, once the API surface has been thought about more and there is more experience using this package within the other SquirrelPHP packages.