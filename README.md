Squirrel Connection
===================

![Test Coverage](https://img.shields.io/badge/style-83%25-success.svg?style=flat-round&label=test%20coverage) ![PHPStan](https://img.shields.io/badge/style-level%20max-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/connection.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/connection)  [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/connection.svg)](https://packagist.org/packages/squirrelphp/connection) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

Provides a slimmed down concise interface for the low level database connection (ConnectionInterface), as a more simple replacement to Doctrine DBAL and a more streamlined/opinionated interface compared to pure PDO. It supports MySQL, MariaDB, Sqlite and PostgreSQL and is currently based on PDO, although that is considered an implementation detail.

Much code for the exception handling was taken from Doctrine DBAL. This library is currently only internally used in [squirrelphp/queries](https://github.com/squirrelphp/queries) and should not be used on its own, as the API is not yet considered completely stable (it is stable-ish). Use [squirrelphp/queries](https://github.com/squirrelphp/queries) instead or even better, use [squirrelphp/entities](https://github.com/squirrelphp/entities) / [squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle) instead, as those are more high-level and stable and will abstract away much of the low-level pain points of databases.

A stable version of this package will eventually come out, once the API surface has been thought about more and there is more experience using this package within the other SquirrelPHP packages.

## Common configuration for all connections

The following options are hardcoded into all connections and mostly differ from the common defaults in PHP database connections:

- Emulation of prepares is turned off, so real query and values separation is enabled instead of emulating it (which is usually the default in PHP). You should not notice this in any way, even in terms of performance: it was tested, and when script and database are running in the same network there is no measureable difference. Your script and database would need to be apart by some distance for any possible effect to manifest. On the other hand, the separation of queries and values has undeniable security benefits and is the way the underlying database client libraries are designed to work.
- For MySQL/MariaDB, the "affected rows" reported for UPDATE queries (retrieved via `ConnectionInterface->rowCount()`) are the "found rows" in the database, even if nothing changed by executing the UPDATE. By default with MySQL/MariaDB in PHP you get the "changed rows", which is a behavior no other database has or even supports, so MySQL/MariaDB is configured to behave more like any other database. Getting the "found rows" count can be useful information, while relying on the "changed rows" count relies on special behavior in one database system.
- Executing multiple statements in one query is disabled. Multiple statements per query were a source of security exploits in the past, are often not easy to port between different database systems and have little real world relevance. Use transactions instead, which is a guaranteed way to execute multiple statements together, or use parallel connections / multiple connections if speed is an issue.