<?php
// Router script for PHP built-in server
// Serve existing static files, otherwise route to index.php

if (PHP_SAPI === 'cli-server') {
	$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
	$file = __DIR__ . $path;
	if ($path !== '/' && is_file($file)) {
		return false;
	}
}

require __DIR__ . '/index.php';


