<?php
session_start();
$payment_id = (int)($_GET['payment_id'] ?? 0);
header("Location: ../payments/view.php?id=$payment_id");
exit;
?>