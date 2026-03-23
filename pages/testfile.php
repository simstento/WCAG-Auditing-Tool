<?php
require '../src/db.php';

$stmt = $pdo->query("SELECT DATABASE()");
echo $stmt->fetchColumn();