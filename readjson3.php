<?php
/**
 * Script: readjson3.php
 * Mục đích: Đọc file flashcard.json và lưu vào database (bảng flashcard_topics và flashcard_words)
 */

require_once 'view/db.php';

$jsonPath = __DIR__ . '/flashcard.json';

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

    // Xóa dữ liệu cũ nếu cần (tùy chọn)
    // $pdo->exec("DELETE FROM flashcard_topics");

    $stmtTopic = $pdo->prepare("INSERT INTO flashcard_topics (name, level) VALUES (:name, :level)");
    $stmtWord = $pdo->prepare("INSERT INTO flashcard_words (topic_id, word, reading, meaning, example) VALUES (:topic_id, :word, :reading, :meaning, :example)");

    $topicCount = 0;
    $wordCount = 0;

    foreach ($data as $item) {
        $theme = $item['theme'] ?? 'Chưa phân loại';
        $level = 'N3'; // Mặc định là N3 cho bộ này

        // Thêm chủ đề
        $stmtTopic->execute([
            ':name' => $theme,
            ':level' => $level
        ]);
        $topicId = $pdo->lastInsertId();
        $topicCount++;

        // Thêm từ vựng thuộc chủ đề
        if (!empty($item['vocabulary']) && is_array($item['vocabulary'])) {
            foreach ($item['vocabulary'] as $vocab) {
                $stmtWord->execute([
                    ':topic_id' => $topicId,
                    ':word' => $vocab['word'] ?? '',
                    ':reading' => $vocab['reading'] ?? '',
                    ':meaning' => $vocab['meaning'] ?? '',
                    ':example' => $vocab['example'] ?? null
                ]);
                $wordCount++;
            }
        }
    }

    $pdo->commit();
    echo "Thành công: Đã thêm $topicCount chủ đề và $wordCount từ vựng vào database.";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Lỗi khi import dữ liệu: " . $e->getMessage());
}
