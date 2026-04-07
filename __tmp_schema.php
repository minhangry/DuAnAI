<?php
require_once 'view/db.php';
$stmt = $pdo->query('DESCRIBE questions');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
