<?php
// readjson2.php: Import dữ liệu từ N3-1.json vào bảng questions
require_once 'view/db.php';
$jsonPath = __DIR__ . '/N3-1.json';

if (!file_exists($jsonPath)) {
    die("File not found: $jsonPath");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);
if (!is_array($data)) {
    die("Invalid JSON or decode error.");
}

$sql = "INSERT INTO questions (level, category, sub_tag, content, option_a, option_b, option_c, option_d, correct_answer, explanation, created_at)
        VALUES (:level, :category, :sub_tag, :content, :option_a, :option_b, :option_c, :option_d, :correct_answer, :explanation, NOW())";
$stmt = $pdo->prepare($sql);

$inserted = 0;
foreach ($data as $q) {
    // Đảm bảo đủ trường, nếu thiếu thì bỏ qua
    if (!isset($q['level'], $q['category'], $q['sub_tag'], $q['content'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_answer'])) continue;
    $stmt->execute([
        ':level' => $q['level'],
        ':category' => $q['category'],
        ':sub_tag' => $q['sub_tag'],
        ':content' => $q['content'],
        ':option_a' => $q['option_a'],
        ':option_b' => $q['option_b'],
        ':option_c' => $q['option_c'],
        ':option_d' => $q['option_d'],
        ':correct_answer' => $q['correct_answer'],
        ':explanation' => $q['explanation'] ?? null
    ]);
    $inserted++;
}
echo "Inserted $inserted records into questions.\n";
