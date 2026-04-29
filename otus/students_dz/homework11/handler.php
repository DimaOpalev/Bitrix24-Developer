<?php
$data = file_get_contents('php://input');
file_put_contents(__DIR__ . '/raw_debug.log', date('H:i:s') . " - " . $data . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/raw_debug.log', date('H:i:s') . " - " . print_r($_POST, true) . "\n", FILE_APPEND);
echo "OK"; 
