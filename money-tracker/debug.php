<?php
session_start();
header('Content-Type: text/plain');

echo "Session ID: " . session_id() . PHP_EOL;
echo "user_id: " . ($_SESSION['user_id'] ?? 'NULL') . PHP_EOL;
echo "username: " . ($_SESSION['username'] ?? 'NULL') . PHP_EOL;
