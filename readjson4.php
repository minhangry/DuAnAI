<?php
/**
 * Script: readjson4.php
 * Mục đích: Đọc file nguphap.json (ngũ pháp N3) và lưu vào database (bảng grammar_lessons)
 */

require_once 'view/db.php';

$jsonPath = __DIR__ . '/nguphap.json';

if (!file_exists($jsonPath)) {
    die("Không tìm thấy file: $jsonPath");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);

if ($data === null) {
    die("Lỗi giải mã JSON.");
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO grammar_lessons (structure_name, meaning, usage_rules, examples, level, created_at)
            VALUES (:structure_name, :meaning, :usage_rules, :examples, :level, NOW())";
    $stmt = $pdo->prepare($sql);

    $insertedCount = 0;

    foreach ($data as $lesson) {
        // Chuyển đổi mảng ví dụ thành định dạng JSON để lưu vào database (cột examples là kiểu JSON)
        $examplesJson = !empty($lesson['examples']) ? json_encode($lesson['examples'], JSON_UNESCAPED_UNICODE) : null;

        $stmt->execute([
            ':structure_name' => $lesson['structure_name'] ?? '',
            ':meaning' => $lesson['meaning'] ?? '',
            ':usage_rules' => $lesson['usage_rules'] ?? '',
            ':examples' => $examplesJson,
            ':level' => $lesson['level'] ?? 'N3'
        ]);
        $insertedCount++;
    }

    $pdo->commit();
    echo "Thành công: Đã thêm $insertedCount bài học ngữ pháp vào database.";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Lỗi khi import dữ liệu ngữ pháp: " . $e->getMessage());
}
