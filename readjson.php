<?php
// Cấu hình DB — chỉnh lại theo môi trường của bạn
$dbHost = '127.0.0.1';
$dbName = 'jlpt_ai_learning';
$dbUser = 'root';
$dbPass = '';

// Đường dẫn tới file data.json (chỉnh nếu khác)
$jsonPath = __DIR__ . '/data.json';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("DB connect error: " . $e->getMessage());
}

if (!file_exists($jsonPath)) {
    die("File not found: $jsonPath");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);
if ($data === null) {
    die("Invalid JSON or decode error.");
}

// Hàm kiểm tra xem một chuỗi có khả năng là tiếng Anh hay không
function looks_like_english(string $s): bool {
    $sTrim = trim($s);
    if ($sTrim === '') return false;
    // có ký tự Latin?
    if (!preg_match('/[A-Za-z]/', $sTrim)) return false;
    $low = strtolower($sTrim);
    // các từ hay xuất hiện trong tiếng Anh / template
    $common = ['the','and','is','font','document','script','template','translation','sentence','front','answer','example','css','html','javascript'];
    $score = 0;
    foreach ($common as $w) {
        if (strpos($low, $w) !== false) $score++;
    }
    // tỉ lệ ký tự Latin trên tổng
    preg_match_all('/[A-Za-z]/', $sTrim, $mLetters);
    preg_match_all('/./u', $sTrim, $mAll);
    $letters = count($mLetters[0]);
    $all = count($mAll[0]);
    $ratio = $all ? ($letters / $all) : 0;
    // nếu có nhiều ký tự Latin hoặc chứa từ common => nghi là tiếng Anh
    return ($ratio > 0.25) || ($score > 0);
}

// Duyệt đệ quy để tìm các trường có nội dung tiếng Anh
function find_english_fields($value, $path = '') {
    $found = [];
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $subpath = $path === '' ? $k : ($path . '.' . $k);
            $found = array_merge($found, find_english_fields($v, $subpath));
        }
    } elseif (is_string($value)) {
        if (looks_like_english($value)) {
            $found[] = ['path' => $path, 'text' => $value];
        }
    }
    return $found;
}

// Chuẩn bị câu lệnh INSERT đúng với bảng grammar_lessons
$sql = "INSERT INTO grammar_lessons (structure_name, meaning, usage_rules, examples, level, created_at)
        VALUES (:structure_name, :meaning, :usage_rules, :examples, :level, NOW())";
$stmt = $pdo->prepare($sql);

$inserted = 0;
if (!empty($data['notes']) && is_array($data['notes'])) {
    foreach ($data['notes'] as $note) {
        // Lấy dữ liệu từ trường fields (theo mẫu data.json bạn gửi)
        $fields = $note['fields'] ?? [];
        // Mapping: tuỳ vào cấu trúc fields, bạn có thể cần điều chỉnh lại thứ tự dưới đây
        $structure_name = $fields[1] ?? null; // ví dụ: ~わけだ
        $meaning = $fields[2] ?? null; // ví dụ: giải thích ý nghĩa
        $usage_rules = $fields[3] ?? null; // ví dụ: cấu trúc ngữ pháp
        // Lấy ví dụ: gom các fields còn lại thành mảng ví dụ
        $examplesArr = [];
        for ($i = 4; $i < count($fields); $i++) {
            if (trim($fields[$i]) !== '') {
                $examplesArr[] = $fields[$i];
            }
        }
        $examples = !empty($examplesArr) ? json_encode($examplesArr, JSON_UNESCAPED_UNICODE) : null;
        // Level: lấy từ tags hoặc để null nếu không có
        $level = null;
        if (!empty($note['tags']) && is_array($note['tags'])) {
            foreach ($note['tags'] as $tag) {
                if (preg_match('/N[2-5]/', $tag)) {
                    $level = $tag;
                    break;
                }
            }
        }
        // Nếu không có level thì bỏ qua bản ghi này
        if (!$structure_name || !$meaning || !$usage_rules || !$level) continue;
        $stmt->execute([
            ':structure_name' => $structure_name,
            ':meaning' => $meaning,
            ':usage_rules' => $usage_rules,
            ':examples' => $examples,
            ':level' => $level
        ]);
        $inserted++;
    }
}
echo "Inserted $inserted records into grammar_lessons.\n";