<?php
require_once 'C:\xampp\htdocs\gebi1\vendor\autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('C:\xampp\htdocs\gebi1');
$dotenv->load();
echo "SID: " . ($_ENV['TWILIO_SID'] ?? 'missing') . "\n";
echo "TOKEN: " . ($_ENV['TWILIO_TOKEN'] ?? 'missing') . "\n";
echo "FROM: " . ($_ENV['TWILIO_FROM_NUMBER'] ?? 'missing') . "\n";
?>