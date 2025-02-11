<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:fetchAll\\(\\) should return list\\<array\\<string, bool\\|float\\|int\\|string\\|null\\>\\> but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:fetchOne\\(\\) should return array\\<string, bool\\|float\\|int\\|string\\|null\\>\\|null but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) has parameter \\$entry with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$entry of method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
