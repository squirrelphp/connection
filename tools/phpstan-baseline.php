<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) has parameter \\$entry with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	// identifier: missingType.iterableValue
	'message' => '#^Method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#1 \\$entry of method Squirrel\\\\Connection\\\\PDO\\\\ConnectionPDO\\:\\:resolveStreamsinEntry\\(\\) expects array, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/PDO/ConnectionPDO.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
