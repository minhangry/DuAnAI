<?php

function jlpt_ai_get_latest_result_id(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT id FROM exam_results WHERE user_id = ? ORDER BY exam_date DESC, id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $resultId = $stmt->fetchColumn();

    return $resultId ? (int) $resultId : null;
}

function jlpt_ai_detect_skill($category, $subTag)
{
    $combined = mb_strtolower(trim($category . ' ' . $subTag), 'UTF-8');

    if (
        mb_strpos($combined, 'kanji') !== false ||
        mb_strpos($combined, 'hán') !== false ||
        mb_strpos($combined, 'chữ hán') !== false ||
        mb_strpos($combined, 'cách đọc') !== false ||
        mb_strpos($combined, 'viết kanji') !== false
    ) {
        return 'kanji';
    }

    if (
        mb_strpos($combined, 'ngữ pháp') !== false ||
        mb_strpos($combined, 'trợ từ') !== false ||
        mb_strpos($combined, 'kính ngữ') !== false ||
        mb_strpos($combined, 'trích dẫn') !== false ||
        mb_strpos($combined, 'đoán') !== false ||
        mb_strpos($combined, 'giới hạn') !== false
    ) {
        return 'grammar';
    }

    if (mb_strpos($combined, 'đọc hiểu') !== false || mb_strpos($combined, 'đọc') !== false) {
        return 'reading';
    }

    return 'vocabulary';
}

function jlpt_ai_skill_meta($skillKey)
{
    $map = [
        'grammar' => [
            'label' => 'Ngữ pháp',
            'title_prefix' => 'Ôn lại ngữ pháp',
            'summary' => 'Bạn đang nhầm các cấu trúc ngữ pháp và cách dùng trong ngữ cảnh.',
        ],
        'kanji' => [
            'label' => 'Chữ Hán',
            'title_prefix' => 'Củng cố chữ Hán',
            'summary' => 'Bạn đang gặp lỗi về Kanji, cách đọc hoặc cách viết từ.',
        ],
        'reading' => [
            'label' => 'Đọc hiểu',
            'title_prefix' => 'Luyện đọc hiểu',
            'summary' => 'Bạn cần luyện thêm cách nắm ý chính và suy luận trong đoạn đọc.',
        ],
        'vocabulary' => [
            'label' => 'Từ vựng',
            'title_prefix' => 'Củng cố từ vựng',
            'summary' => 'Bạn đang nhầm nghĩa từ, từ đồng nghĩa hoặc cách dùng từ.',
        ],
    ];

    return $map[$skillKey] ?? $map['vocabulary'];
}

function jlpt_ai_personalized_download_url($resultId, $mode, $skillKey = '', $topic = '')
{
    $url = 'download_personalized_resource.php?result_id=' . urlencode((string) $resultId)
        . '&mode=' . urlencode($mode);

    if ($skillKey !== '') {
        $url .= '&skill=' . urlencode($skillKey);
    }

    if ($topic !== '') {
        $url .= '&topic=' . urlencode($topic);
    }

    return $url;
}

function jlpt_ai_build_download_resource($resultId, $mode, $title, $description, $skillKey = '', $topic = '')
{
    return [
        'id' => 'personalized_' . $mode . '_' . md5($resultId . '|' . $skillKey . '|' . $topic),
        'title' => $title,
        'description' => $description,
        'url' => jlpt_ai_personalized_download_url($resultId, $mode, $skillKey, $topic),
        'download' => false,
        'format' => 'html',
    ];
}

function jlpt_ai_find_grammar_resources(PDO $pdo, $level, $topic)
{
    $resources = [];
    $term = trim($topic);

    if ($term !== '') {
        $stmt = $pdo->prepare(
            "SELECT id, structure_name, meaning
             FROM grammar_lessons
             WHERE level = ?
               AND (structure_name LIKE ? OR meaning LIKE ? OR usage_rules LIKE ?)
             ORDER BY created_at DESC
             LIMIT 2"
        );
        $like = '%' . $term . '%';
        $stmt->execute([$level, $like, $like, $like]);

        foreach ($stmt->fetchAll() as $lesson) {
            $resources[] = [
                'id' => 'grammar_lesson_' . $lesson['id'],
                'title' => 'Bài ngữ pháp: ' . $lesson['structure_name'],
                'description' => mb_substr(strip_tags((string) $lesson['meaning']), 0, 140, 'UTF-8'),
                'url' => 'grammar.php?level=' . urlencode($level) . '&search=' . urlencode($lesson['structure_name']),
                'download' => false,
            ];
        }
    }

    return $resources;
}

function jlpt_ai_find_flashcard_resources(PDO $pdo, $level)
{
    $resources = [];
    $stmt = $pdo->prepare("SELECT id, name FROM flashcard_topics WHERE level = ? ORDER BY created_at DESC LIMIT 2");
    $stmt->execute([$level]);

    foreach ($stmt->fetchAll() as $topic) {
        $resources[] = [
            'id' => 'flashcard_topic_' . $topic['id'],
            'title' => 'Flashcard chủ đề: ' . $topic['name'],
            'description' => 'Ôn nhanh qua bộ flashcard cùng cấp độ để vá lỗ hổng kiến thức vừa sai.',
            'url' => 'flashcard.php?topic_id=' . urlencode($topic['id']),
            'download' => false,
        ];
    }

    return $resources;
}

function jlpt_ai_find_resources(PDO $pdo, $resultId, $level, $skillKey, $topic)
{
    $meta = jlpt_ai_skill_meta($skillKey);
    $resources = [
        jlpt_ai_build_download_resource(
            $resultId,
            'group',
            'Tài liệu cá nhân hóa: ' . $meta['label'] . ' - ' . $topic,
            'Trang HTML tạo riêng từ các câu bạn vừa làm sai, có phân tích, bài tập sát lỗi và đáp án ẩn/hiện.',
            $skillKey,
            $topic
        ),
    ];

    if ($skillKey === 'grammar') {
        $resources = array_merge($resources, jlpt_ai_find_grammar_resources($pdo, $level, $topic));
    }

    if ($skillKey === 'kanji' || $skillKey === 'vocabulary') {
        $resources = array_merge($resources, jlpt_ai_find_flashcard_resources($pdo, $level));
    }

    if ($skillKey === 'grammar' || $skillKey === 'reading') {
        $resources[] = [
            'id' => 'grammar_library_' . $skillKey,
            'title' => 'Mở thư viện ngữ pháp ' . $level,
            'description' => 'Tra nhanh toàn bộ bài học cùng cấp độ để ôn sâu hơn sau khi xem lộ trình.',
            'url' => 'grammar.php?level=' . urlencode($level),
            'download' => false,
        ];
    }

    return array_slice($resources, 0, 3);
}

function jlpt_ai_build_personalized_doc(array $group, array $roadmapData)
{
    $skillKey = $group['skill_key'];
    $skillMeta = jlpt_ai_skill_meta($skillKey);
    $topic = $group['topic'];
    $summary = $roadmapData['summary'] ?? [];
    $level = $roadmapData['level'] ?? 'N3';
    $mistakes = $group['mistakes'] ?? [];

    $title = 'TÀI LIỆU CÁ NHÂN HÓA JLPT - ' . mb_strtoupper($skillMeta['label'] . ' - ' . $topic, 'UTF-8');
    $lines = [];
    $lines[] = $title;
    $lines[] = '';
    $lines[] = 'Trình độ: ' . $level;
    $lines[] = 'Điểm bài gần nhất: ' . ($summary['score'] ?? 0) . '/100';
    $lines[] = 'Số câu sai trong nhóm này: ' . ($group['count'] ?? 0);
    $lines[] = '';
    $lines[] = '----------------------------------------';
    $lines[] = '1. VÌ SAO BẠN ĐƯỢC GỢI Ý TÀI LIỆU NÀY';
    $lines[] = '----------------------------------------';
    $lines[] = 'Bạn được gợi ý tài liệu này vì trong bài JLPT gần nhất, hệ thống phát hiện bạn sai ở nhóm: ' . $skillMeta['label'] . ' - ' . $topic . '.';
    $lines[] = $skillMeta['summary'];
    $lines[] = '';
    $lines[] = 'Các câu sai liên quan:';
    foreach ($mistakes as $index => $mistake) {
        $lines[] = ($index + 1) . '. Câu #' . $mistake['question_id'] . ': ' . $mistake['question'];
    }
    $lines[] = '';
    $lines[] = '----------------------------------------';
    $lines[] = '2. PHÂN TÍCH LỖI SAI THỰC TẾ CỦA BẠN';
    $lines[] = '----------------------------------------';
    foreach ($mistakes as $index => $mistake) {
        $lines[] = 'Lỗi ' . ($index + 1) . ':';
        $lines[] = '- Câu hỏi: ' . $mistake['question'];
        $lines[] = '- Bạn chọn: ' . $mistake['user_answer'];
        $lines[] = '- Đáp án đúng: ' . $mistake['correct_answer'];
        if (!empty($mistake['explanation'])) {
            $lines[] = '- Giải thích: ' . $mistake['explanation'];
        }
        $lines[] = '';
    }

    $lines = array_merge($lines, jlpt_ai_build_knowledge_section($skillKey, $topic, $mistakes));
    $lines = array_merge($lines, jlpt_ai_build_examples_section($skillKey, $topic, $mistakes));
    $lines = array_merge($lines, jlpt_ai_build_exercise_section($skillKey, $topic, $mistakes));

    $lines[] = '----------------------------------------';
    $lines[] = '6. GỢI Ý ÔN TẬP TIẾP THEO';
    $lines[] = '----------------------------------------';
    $lines[] = '- Đọc lại toàn bộ câu sai trong nhóm này 2 lần.';
    $lines[] = '- Tự che đáp án đúng và trả lời lại.';
    $lines[] = '- Viết thêm 3 câu mới có dùng điểm kiến thức vừa ôn.';
    $lines[] = '- Sau khi học xong, quay lại làm một đề JLPT ' . $level . ' mới để kiểm tra tiến bộ.';
    $lines[] = '';
    $lines[] = 'Tài liệu được tạo tự động từ bài làm của riêng bạn.';

    return implode("\r\n", $lines) . "\r\n";
}

function jlpt_ai_build_knowledge_section($skillKey, $topic, array $mistakes)
{
    $lines = [];
    $lines[] = '----------------------------------------';
    $lines[] = '3. KIẾN THỨC CẦN ÔN LẠI';
    $lines[] = '----------------------------------------';

    if ($skillKey === 'grammar') {
        $lines[] = 'Trọng tâm ngữ pháp: ' . $topic;
        $lines[] = '- Xác định đúng ý nghĩa của mẫu ngữ pháp trong câu.';
        $lines[] = '- Kiểm tra xem mẫu đó đi với câu khẳng định hay phủ định.';
        $lines[] = '- Chú ý sắc thái: lịch sự, trung tính, suy đoán, giới hạn, trích dẫn.';
        $lines[] = '- Khi làm bài, hãy thay từng đáp án vào câu và đọc lại toàn câu để kiểm tra độ tự nhiên.';
    } elseif ($skillKey === 'kanji') {
        $lines[] = 'Trọng tâm chữ Hán: ' . $topic;
        $lines[] = '- Ôn đồng thời 3 lớp: mặt chữ, cách đọc, nghĩa.';
        $lines[] = '- Không học chữ rời rạc, hãy học theo từ hoàn chỉnh trong ngữ cảnh.';
        $lines[] = '- Nếu bạn sai cách đọc, hãy luyện Kanji -> Kana.';
        $lines[] = '- Nếu bạn sai cách viết, hãy luyện Nghĩa -> Kanji.';
    } elseif ($skillKey === 'reading') {
        $lines[] = 'Trọng tâm đọc hiểu: ' . $topic;
        $lines[] = '- Đọc câu hỏi trước rồi mới đọc đoạn văn.';
        $lines[] = '- Gạch chân từ nối và các từ khóa về thời gian, nguyên nhân, kết quả.';
        $lines[] = '- Loại các đáp án dùng từ giống đoạn nhưng sai ý.';
        $lines[] = '- Tóm tắt ý chính của đoạn bằng 1 câu tiếng Việt sau khi đọc.';
    } else {
        $lines[] = 'Trọng tâm từ vựng: ' . $topic;
        $lines[] = '- Phân biệt nghĩa gần nhau nhưng khác sắc thái dùng.';
        $lines[] = '- Ghi nhớ từ bằng ví dụ thực tế thay vì chỉ học nghĩa đơn lẻ.';
        $lines[] = '- Nhóm các từ sai theo chủ đề để ôn lại cùng nhau.';
        $lines[] = '- Chú ý xem từ xuất hiện trong ngữ cảnh nào.';
    }

    $lines[] = '';
    return $lines;
}

function jlpt_ai_build_examples_section($skillKey, $topic, array $mistakes)
{
    $lines = [];
    $lines[] = '----------------------------------------';
    $lines[] = '4. VÍ DỤ MINH HỌA DỰA TRÊN LỖI SAI';
    $lines[] = '----------------------------------------';

    foreach ($mistakes as $index => $mistake) {
        $lines[] = 'Ví dụ ' . ($index + 1) . ':';
        $lines[] = '- Câu gốc: ' . $mistake['question'];
        $lines[] = '- Hướng ôn: tập trung vào ' . $topic . '.';
        if (!empty($mistake['explanation'])) {
            $lines[] = '- Gợi ý hiểu nhanh: ' . $mistake['explanation'];
        }
        $lines[] = '';
    }

    if ($skillKey === 'grammar') {
        $lines[] = 'Ví dụ thêm:';
        $lines[] = '- コーヒーだけ飲みます。';
        $lines[] = '  Tôi chỉ uống cà phê.';
        $lines[] = '- コーヒーしか飲みません。';
        $lines[] = '  Tôi không uống gì ngoài cà phê.';
    } elseif ($skillKey === 'kanji') {
        $lines[] = 'Ví dụ thêm:';
        $lines[] = '- 発見（はっけん）: phát hiện';
        $lines[] = '- 得意（とくい）: sở trường';
        $lines[] = '- 正常（せいじょう）: bình thường';
    } elseif ($skillKey === 'reading') {
        $lines[] = 'Ví dụ thêm:';
        $lines[] = '- Từ nối しかし thường báo hiệu ý phía sau trái với ý trước đó.';
        $lines[] = '- Từ nối だから thường báo hiệu kết quả hoặc kết luận.';
    } else {
        $lines[] = 'Ví dụ thêm:';
        $lines[] = '- きつい: vất vả, nặng';
        $lines[] = '- 大変: khó khăn, vất vả theo nghĩa rộng';
        $lines[] = '- 落ち着く: bình tĩnh, ổn định';
    }

    $lines[] = '';
    return $lines;
}

function jlpt_ai_build_exercise_section($skillKey, $topic, array $mistakes)
{
    $lines = [];
    $lines[] = '----------------------------------------';
    $lines[] = '5. BÀI TẬP LUYỆN THÊM';
    $lines[] = '----------------------------------------';
    $lines[] = 'Bài 1. Xem lại các câu sai của bạn và tự trả lời lại không nhìn đáp án.';
    foreach ($mistakes as $index => $mistake) {
        $lines[] = ($index + 1) . '. ' . $mistake['question'];
    }
    $lines[] = '';

    if ($skillKey === 'grammar') {
        $lines[] = 'Bài 2. Chọn đáp án đúng:';
        $lines[] = '1. 田中さんは明日来る（と・だけ）言いました。';
        $lines[] = '2. お金が500円（だけ・しか）あります。';
        $lines[] = '3. 社長は会議室に（ございます・いらっしゃいます）。';
        $lines[] = '';
        $lines[] = 'Đáp án gợi ý: 1. と  2. だけ  3. いらっしゃいます';
    } elseif ($skillKey === 'kanji') {
        $lines[] = 'Bài 2. Nối từ với cách đọc đúng:';
        $lines[] = '1. 発見   a. とくい';
        $lines[] = '2. 得意   b. せいじょう';
        $lines[] = '3. 正常   c. はっけん';
        $lines[] = '';
        $lines[] = 'Đáp án gợi ý: 1-c  2-a  3-b';
    } elseif ($skillKey === 'reading') {
        $lines[] = 'Bài 2. Đọc yêu cầu rồi trả lời:';
        $lines[] = '- Đoạn văn đang nói về ai?';
        $lines[] = '- Từ nối quan trọng nhất trong đoạn là gì?';
        $lines[] = '- Ý chính của đoạn có thể tóm tắt trong 1 câu như thế nào?';
    } else {
        $lines[] = 'Bài 2. Chọn từ đúng:';
        $lines[] = '1. 今回の仕事はとても（きつい・静か）です。';
        $lines[] = '2. まず（落ち着いて・にぎやかに）行動してください。';
        $lines[] = '3. 店で車の（カタログ・落ち着く）をもらいました。';
        $lines[] = '';
        $lines[] = 'Đáp án gợi ý: 1. きつい  2. 落ち着いて  3. カタログ';
    }

    $lines[] = '';
    return $lines;
}

function jlpt_ai_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function jlpt_ai_build_question_exercises(array $mistakes)
{
    $items = [];
    foreach ($mistakes as $mistake) {
        if (!empty($mistake['similar_question'])) {
            $sq = $mistake['similar_question'];
            $items[] = [
                'prompt' => 'Làm câu tương tự câu #' . $mistake['question_id'] . ' để ôn tập.',
                'question' => $sq['content'],
                'hint' => '',
                'answer' => 'Đáp án đúng là: ' . $sq['correct_answer'],
                'check_type' => 'multiple_choice',
                'expected' => $sq['correct_answer'],
                'options' => [
                    'A' => $sq['option_a'],
                    'B' => $sq['option_b'],
                    'C' => $sq['option_c'],
                    'D' => $sq['option_d'],
                ]
            ];
        } else {
            if (!empty($mistake['option_a'])) {
                $items[] = [
                    'prompt' => 'Làm lại câu gốc bạn đã sai (do không có câu tương đồng trong CSDL).',
                    'question' => $mistake['question'],
                    'hint' => '',
                    'answer' => 'Đáp án đúng: ' . $mistake['correct_answer'],
                    'check_type' => 'multiple_choice',
                    'expected' => $mistake['correct_answer'],
                    'options' => [
                        'A' => $mistake['option_a'],
                        'B' => $mistake['option_b'],
                        'C' => $mistake['option_c'],
                        'D' => $mistake['option_d'],
                    ]
                ];
            } else {
                $items[] = [
                    'prompt' => 'Làm lại câu #' . $mistake['question_id'] . ' mà không nhìn đáp án.',
                    'question' => $mistake['question'],
                    'hint' => '',
                    'answer' => 'Đáp án đúng: ' . $mistake['correct_answer'],
                    'check_type' => 'contains',
                    'expected' => (string) $mistake['correct_answer'],
                ];
            }
        }
    }

    return $items;
}

function jlpt_ai_build_focus_exercises($skillKey, $topic, array $mistakes)
{
    $items = [];

    foreach ($mistakes as $mistake) {
        if (!empty($mistake['option_a'])) {
            $items[] = [
                'prompt' => 'Làm lại câu gốc bạn đã sai để củng cố kiến thức.',
                'question' => $mistake['question'],
                'hint' => '',
                'answer' => 'Đáp án đúng là: ' . $mistake['correct_answer'] . (!empty($mistake['explanation']) ? ' - ' . $mistake['explanation'] : ''),
                'check_type' => 'multiple_choice',
                'expected' => $mistake['correct_answer'],
                'options' => [
                    'A' => $mistake['option_a'],
                    'B' => $mistake['option_b'],
                    'C' => $mistake['option_c'],
                    'D' => $mistake['option_d'],
                ]
            ];
        } else {
            $items[] = [
                'prompt' => 'Làm lại câu gốc bạn đã sai.',
                'question' => $mistake['question'],
                'hint' => '',
                'answer' => 'Đáp án: ' . $mistake['correct_answer'],
                'check_type' => 'non_empty',
                'expected' => '',
            ];
        }
    }

    return $items;
}

function jlpt_ai_render_toggle_block($id, $label, $contentHtml)
{
    return '<button class="answer-toggle" type="button" data-target="' . jlpt_ai_escape($id) . '" data-label="' . jlpt_ai_escape($label) . '">' . jlpt_ai_escape($label) . '</button>'
        . '<div class="answer-panel" id="' . jlpt_ai_escape($id) . '">' . $contentHtml . '</div>';
}

function jlpt_ai_render_personalized_html(array $docData)
{
    $isExam = ($docData['mode'] ?? 'group') === 'exam';
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo jlpt_ai_escape($docData['title'] ?? 'Tài liệu cá nhân hóa'); ?></title>
    <style>
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f4f7fb; color: #0f172a; line-height: 1.6; }
        .page { max-width: 1100px; margin: 0 auto; padding: 28px 20px 56px; }
        .hero, .section { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        .hero { padding: 28px; background: linear-gradient(135deg, rgba(13,148,136,.96), rgba(2,132,199,.9)); color: #fff; margin-bottom: 20px; }
        .hero h1 { margin: 0 0 8px; font-size: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 18px; }
        .stat { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); border-radius: 18px; padding: 14px 16px; }
        .stat-label { font-size: .8rem; text-transform: uppercase; opacity: .82; }
        .stat-value { font-size: 1.5rem; font-weight: 800; margin-top: 4px; }
        .section { padding: 22px; margin-bottom: 18px; }
        .toolbar { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
        .mistake, .exercise, .card { border: 1px solid #dbe3ef; border-radius: 18px; background: #fff; padding: 16px; }
        .mistake { border-left: 6px solid #dc2626; margin-bottom: 14px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
        .muted { color: #475569; }
        .answer-toggle { margin-top: 12px; border: 0; border-radius: 999px; background: linear-gradient(135deg, #0d9488, #0284c7); color: #fff; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .print-btn, .check-btn { margin-top: 12px; border: 0; border-radius: 999px; background: #0f172a; color: #fff; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .exercise-input { width:100%; min-height:90px; margin-top:12px; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; font:inherit; }
        .check-result { display:none; margin-top:10px; border-radius:14px; padding:12px 14px; font-weight:600; }
        .check-result.show { display:block; }
        .check-result.ok { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .check-result.warn { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
        .answer-panel { display: none; margin-top: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 14px; }
        .answer-panel.open { display: block; }
        ul.clean { margin: 0; padding-left: 18px; }
        @media print { .answer-panel { display:block !important; } .answer-toggle { display:none; } }
    </style>
</head>
<body>
<div class="page">
    <div class="hero">
        <h1><?php echo jlpt_ai_escape($docData['title'] ?? 'Tài liệu cá nhân hóa'); ?></h1>
        <div><?php echo jlpt_ai_escape($docData['subtitle'] ?? ''); ?></div>
        <div class="toolbar">
            <button class="print-btn" type="button" onclick="window.print()">In / PDF</button>
        </div>
        <div class="stats">
            <div class="stat"><div class="stat-label">Trình độ</div><div class="stat-value"><?php echo jlpt_ai_escape($docData['level'] ?? 'N3'); ?></div></div>
            <div class="stat"><div class="stat-label">Điểm bài thi</div><div class="stat-value"><?php echo jlpt_ai_escape((string) ($docData['score'] ?? 0)); ?>/100</div></div>
            <div class="stat"><div class="stat-label"><?php echo $isExam ? 'Số câu sai' : 'Nhóm lỗi'; ?></div><div class="stat-value"><?php echo $isExam ? jlpt_ai_escape((string) ($docData['total_incorrect'] ?? 0)) : jlpt_ai_escape($docData['group']['title'] ?? ''); ?></div></div>
        </div>
    </div>
<?php if (!$isExam): ?>
    <div class="section">
        <h2>Phân tích lỗi sai thực tế của bạn</h2>
        <?php foreach (($docData['mistakes'] ?? []) as $mistake): ?>
            <div class="mistake">
                <div><strong>Câu #<?php echo jlpt_ai_escape((string) $mistake['question_id']); ?></strong></div>
                <div><?php echo jlpt_ai_escape($mistake['question']); ?></div>
                <div><strong>Bạn chọn:</strong> <?php echo jlpt_ai_escape($mistake['user_answer']); ?></div>
                <div><strong>Đáp án đúng:</strong> <?php echo jlpt_ai_escape($mistake['correct_answer']); ?></div>
                <?php if (!empty($mistake['explanation'])): ?><div><strong>Giải thích:</strong> <?php echo jlpt_ai_escape($mistake['explanation']); ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="section">
        <h2>Bài tập sát với câu sai của bạn</h2>
        <?php foreach (($docData['question_exercises'] ?? []) as $index => $exercise): ?>
            <div class="exercise">
                <div><strong>Yêu cầu:</strong> <?php echo jlpt_ai_escape($exercise['prompt']); ?></div>
                <div><?php echo jlpt_ai_escape($exercise['question']); ?></div>
                <?php if (!empty($exercise['hint'])): ?>
                <div class="muted"><strong>Gợi ý:</strong> <?php echo jlpt_ai_escape($exercise['hint']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($exercise['options'])): ?>
                    <div class="exercise-options" style="margin-top:12px;">
                        <?php foreach($exercise['options'] as $key => $val): ?>
                            <label style="display:block; margin-bottom:8px; cursor:pointer;">
                                <input type="radio" name="q_ex_g_<?php echo $index; ?>" value="<?php echo $key; ?>" class="exercise-radio" data-expected="<?php echo jlpt_ai_escape($exercise['expected']); ?>">
                                <strong><?php echo $key; ?>.</strong> <?php echo jlpt_ai_escape($val); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <textarea class="exercise-input" placeholder="Nhập đáp án của bạn..." data-check-type="<?php echo jlpt_ai_escape($exercise['check_type'] ?? 'non_empty'); ?>" data-expected="<?php echo jlpt_ai_escape($exercise['expected'] ?? ''); ?>"></textarea>
                <?php endif; ?>
                
                <button class="check-btn" type="button" style="margin-top:12px;">Chấm ngay</button>
                <div class="check-result"></div>
                <?php echo jlpt_ai_render_toggle_block('qe-' . $index, 'Hiện đáp án', '<div>' . jlpt_ai_escape($exercise['answer']) . '</div>'); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="section">
        <h2>Bài tập vận dụng thêm</h2>
        <?php foreach (($docData['focus_exercises'] ?? []) as $index => $exercise): ?>
            <div class="exercise">
                <div><strong>Yêu cầu:</strong> <?php echo jlpt_ai_escape($exercise['prompt']); ?></div>
                <div><?php echo jlpt_ai_escape($exercise['question']); ?></div>
                <?php if (!empty($exercise['hint'])): ?>
                <div class="muted"><strong>Gợi ý:</strong> <?php echo jlpt_ai_escape($exercise['hint']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($exercise['options'])): ?>
                    <div class="exercise-options" style="margin-top:12px;">
                        <?php foreach($exercise['options'] as $key => $val): ?>
                            <label style="display:block; margin-bottom:8px; cursor:pointer;">
                                <input type="radio" name="f_ex_g_<?php echo $index; ?>" value="<?php echo $key; ?>" class="exercise-radio" data-expected="<?php echo jlpt_ai_escape($exercise['expected']); ?>">
                                <strong><?php echo $key; ?>.</strong> <?php echo jlpt_ai_escape($val); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <textarea class="exercise-input" placeholder="Nhập đáp án của bạn..." data-check-type="<?php echo jlpt_ai_escape($exercise['check_type'] ?? 'non_empty'); ?>" data-expected="<?php echo jlpt_ai_escape($exercise['expected'] ?? ''); ?>"></textarea>
                <?php endif; ?>

                <button class="check-btn" type="button" style="margin-top:12px;">Chấm ngay</button>
                <div class="check-result"></div>
                <?php echo jlpt_ai_render_toggle_block('fe-' . $index, 'Hiện lời giải', '<div>' . jlpt_ai_escape($exercise['answer']) . '</div>'); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php foreach (($docData['groups'] ?? []) as $groupIndex => $group): ?>
        <div class="section">
            <h2><?php echo jlpt_ai_escape($group['summary']['title']); ?></h2>
            <div class="muted"><?php echo jlpt_ai_escape($group['summary']['summary']); ?></div>
            <?php foreach (($group['mistakes'] ?? []) as $mistake): ?>
                <div class="mistake">
                    <div><strong>Câu #<?php echo jlpt_ai_escape((string) $mistake['question_id']); ?></strong></div>
                    <div><?php echo jlpt_ai_escape($mistake['question']); ?></div>
                    <div><strong>Bạn chọn:</strong> <?php echo jlpt_ai_escape($mistake['user_answer']); ?></div>
                    <div><strong>Đáp án đúng:</strong> <?php echo jlpt_ai_escape($mistake['correct_answer']); ?></div>
                    <?php if (!empty($mistake['explanation'])): ?><div><strong>Giải thích:</strong> <?php echo jlpt_ai_escape($mistake['explanation']); ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php foreach (($group['question_exercises'] ?? []) as $index => $exercise): ?>
                <div class="exercise">
                    <div><strong>Yêu cầu:</strong> <?php echo jlpt_ai_escape($exercise['prompt']); ?></div>
                    <div><?php echo jlpt_ai_escape($exercise['question']); ?></div>
                    <?php if (!empty($exercise['hint'])): ?>
                    <div class="muted"><strong>Gợi ý:</strong> <?php echo jlpt_ai_escape($exercise['hint']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($exercise['options'])): ?>
                        <div class="exercise-options" style="margin-top:12px;">
                            <?php foreach($exercise['options'] as $key => $val): ?>
                                <label style="display:block; margin-bottom:8px; cursor:pointer;">
                                    <input type="radio" name="e_ex_g_<?php echo $groupIndex; ?>_<?php echo $index; ?>" value="<?php echo $key; ?>" class="exercise-radio" data-expected="<?php echo jlpt_ai_escape($exercise['expected']); ?>">
                                    <strong><?php echo $key; ?>.</strong> <?php echo jlpt_ai_escape($val); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <textarea class="exercise-input" placeholder="Nhập đáp án của bạn..." data-check-type="<?php echo jlpt_ai_escape($exercise['check_type'] ?? 'non_empty'); ?>" data-expected="<?php echo jlpt_ai_escape($exercise['expected'] ?? ''); ?>"></textarea>
                    <?php endif; ?>

                    <button class="check-btn" type="button" style="margin-top:12px;">Chấm ngay</button>
                    <div class="check-result"></div>
                    <?php echo jlpt_ai_render_toggle_block('eg-' . $groupIndex . '-' . $index, 'Hiện đáp án', '<div>' . jlpt_ai_escape($exercise['answer']) . '</div>'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
    <div class="section">
        <h2>Bước tiếp theo</h2>
        <ul class="clean">
            <?php foreach (($docData['next_steps'] ?? []) as $step): ?><li><?php echo jlpt_ai_escape($step); ?></li><?php endforeach; ?>
        </ul>
    </div>
</div>
<script>
document.querySelectorAll('.answer-toggle').forEach(function(button){
  button.addEventListener('click', function(){
    var target=document.getElementById(button.getAttribute('data-target'));
    if(!target){return;}
    target.classList.toggle('open');
    button.textContent = target.classList.contains('open') ? 'Ẩn đáp án' : button.getAttribute('data-label');
  });
});
document.querySelectorAll('.check-btn').forEach(function(button){
  button.addEventListener('click', function(){
    var wrap = button.closest('.exercise');
    if(!wrap){return;}
    var result = wrap.querySelector('.check-result');
    if(!result){return;}

    var radioGroup = wrap.querySelectorAll('.exercise-radio');
    if (radioGroup.length > 0) {
        var selected = null;
        var expected = radioGroup[0].dataset.expected.toLowerCase();
        radioGroup.forEach(function(r) {
            if (r.checked) selected = r.value.toLowerCase();
        });
        
        result.className = 'check-result show';
        if (!selected) {
            result.classList.add('warn');
            result.textContent = 'Bạn chưa chọn đáp án.';
            return;
        }
        if (selected === expected) {
            result.classList.add('ok');
            result.textContent = 'Chính xác! Đáp án đúng là ' + expected.toUpperCase();
        } else {
            result.classList.add('warn');
            result.textContent = 'Chưa chính xác. Bạn vừa chọn ' + selected.toUpperCase() + '. Hãy xem đáp án đúng.';
        }
        return;
    }

    var input = wrap.querySelector('.exercise-input');
    if(!input){return;}
    var user = (input.value || '').trim();
    var checkType = input.dataset.checkType || 'non_empty';
    var expected = (input.dataset.expected || '').trim().toLowerCase();
    var normalized = user.toLowerCase();
    result.className = 'check-result show';
    if(!user){
      result.classList.add('warn');
      result.textContent = 'Bạn chưa nhập đáp án.';
      return;
    }
    if(checkType === 'contains'){
      if(normalized.indexOf(expected) !== -1){
        result.classList.add('ok');
        result.textContent = 'Khớp với đáp án trọng tâm.';
      } else {
        result.classList.add('warn');
        result.textContent = 'Chưa khớp đáp án trọng tâm. Hãy xem gợi ý hoặc đáp án mẫu.';
      }
      return;
    }
    result.classList.add('ok');
    result.textContent = 'Đã ghi nhận câu trả lời. Với dạng tự luận, hãy đối chiếu thêm phần gợi ý và đáp án mẫu.';
  });
});
</script>
</body>
</html>
<?php
    return ob_get_clean();
}

function jlpt_ai_build_personalized_doc_data(PDO $pdo, array $group, array $roadmapData)
{
    $summary = $roadmapData['summary'] ?? [];
    $skillMeta = jlpt_ai_skill_meta($group['skill_key']);

    $mistakes = $group['mistakes'] ?? [];
    foreach ($mistakes as &$mistake) {
        if (empty($mistake['option_a']) || empty($mistake['similar_question'])) {
            $stmt = $pdo->prepare("SELECT level, category, sub_tag, option_a, option_b, option_c, option_d FROM questions WHERE id = ?");
            $stmt->execute([$mistake['question_id']]);
            $qInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($qInfo) {
                if (empty($mistake['option_a'])) {
                    $mistake['option_a'] = $qInfo['option_a'];
                    $mistake['option_b'] = $qInfo['option_b'];
                    $mistake['option_c'] = $qInfo['option_c'];
                    $mistake['option_d'] = $qInfo['option_d'];
                }
                if (empty($mistake['similar_question'])) {
                    $stmtRand = $pdo->prepare("SELECT content, option_a, option_b, option_c, option_d, correct_answer FROM questions WHERE category = ? AND sub_tag = ? AND level = ? AND id != ? ORDER BY RAND() LIMIT 1");
                    $stmtRand->execute([$qInfo['category'], $qInfo['sub_tag'], $qInfo['level'], $mistake['question_id']]);
                    $sq = $stmtRand->fetch(PDO::FETCH_ASSOC);
                    if ($sq) {
                        $mistake['similar_question'] = $sq;
                    }
                }
            }
        }
    }
    unset($mistake);

    return [
        'mode' => 'group',
        'title' => 'Tài liệu cá nhân hóa - ' . $skillMeta['label'] . ' - ' . $group['topic'],
        'subtitle' => 'Được tạo riêng từ các câu bạn vừa làm sai trong bài JLPT gần nhất.',
        'level' => $roadmapData['level'] ?? 'N3',
        'score' => $summary['score'] ?? 0,
        'group' => [
            'title' => $skillMeta['label'] . ' - ' . $group['topic'],
        ],
        'mistakes' => $mistakes,
        'question_exercises' => jlpt_ai_build_question_exercises($mistakes),
        'focus_exercises' => jlpt_ai_build_focus_exercises($group['skill_key'], $group['topic'], $mistakes),
        'next_steps' => [
            'Đọc lại toàn bộ câu sai trong nhóm này ít nhất 2 lần.',
            'Che đáp án đúng và tự làm lại trước khi xem lời giải.',
            'Viết thêm 3 câu mới để chuyển kiến thức từ nhận biết sang vận dụng.',
            'Sau khi học xong, làm lại một đề JLPT mới để kiểm tra tiến bộ.',
        ],
    ];
}

function jlpt_ai_build_exam_doc_data(PDO $pdo, array $roadmapData)
{
    $summary = $roadmapData['summary'] ?? [];
    $groups = [];
    foreach (($roadmapData['mistake_groups'] ?? []) as $group) {
        $meta = jlpt_ai_skill_meta($group['skill_key']);
        
        $mistakes = $group['mistakes'] ?? [];
        foreach ($mistakes as &$mistake) {
            if (empty($mistake['option_a']) || empty($mistake['similar_question'])) {
                $stmt = $pdo->prepare("SELECT level, category, sub_tag, option_a, option_b, option_c, option_d FROM questions WHERE id = ?");
                $stmt->execute([$mistake['question_id']]);
                $qInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($qInfo) {
                    if (empty($mistake['option_a'])) {
                        $mistake['option_a'] = $qInfo['option_a'];
                        $mistake['option_b'] = $qInfo['option_b'];
                        $mistake['option_c'] = $qInfo['option_c'];
                        $mistake['option_d'] = $qInfo['option_d'];
                    }
                    if (empty($mistake['similar_question'])) {
                        $stmtRand = $pdo->prepare("SELECT content, option_a, option_b, option_c, option_d, correct_answer FROM questions WHERE category = ? AND sub_tag = ? AND level = ? AND id != ? ORDER BY RAND() LIMIT 1");
                        $stmtRand->execute([$qInfo['category'], $qInfo['sub_tag'], $qInfo['level'], $mistake['question_id']]);
                        $sq = $stmtRand->fetch(PDO::FETCH_ASSOC);
                        if ($sq) {
                            $mistake['similar_question'] = $sq;
                        }
                    }
                }
            }
        }
        unset($mistake);

        $groups[] = [
            'summary' => [
                'title' => $meta['label'] . ' - ' . $group['topic'],
                'summary' => $group['summary'],
            ],
            'mistakes' => $mistakes,
            'question_exercises' => jlpt_ai_build_question_exercises($mistakes),
        ];
    }

    return [
        'mode' => 'exam',
        'title' => 'Hồ sơ ôn tập tổng hợp JLPT ' . ($roadmapData['level'] ?? 'N3'),
        'subtitle' => 'Tổng hợp toàn bộ lỗi sai của bài thi gần nhất và kế hoạch ôn tập đi kèm.',
        'level' => $roadmapData['level'] ?? 'N3',
        'score' => $summary['score'] ?? 0,
        'total_incorrect' => $summary['total_incorrect'] ?? 0,
        'groups' => $groups,
        'next_steps' => [
            'Ưu tiên học nhóm lỗi có số câu sai nhiều nhất trước.',
            'Sau mỗi nhóm lỗi, làm lại ngay các câu sai tương ứng.',
            'Kết hợp học tài liệu cá nhân hóa với thư viện ngữ pháp hoặc flashcard của hệ thống.',
            'Làm một đề mới sau khi hoàn thành toàn bộ roadmap để đo lại năng lực.',
        ],
    ];
}

function jlpt_ai_get_roadmap_by_result(PDO $pdo, $userId, $resultId)
{
    $stmt = $pdo->prepare("SELECT roadmap_content FROM ai_roadmaps WHERE user_id = ? ORDER BY created_at DESC, id DESC");
    $stmt->execute([$userId]);

    foreach ($stmt->fetchAll() as $row) {
        $data = json_decode($row['roadmap_content'], true);
        if (!empty($data['generated_from_result_id']) && (int) $data['generated_from_result_id'] === (int) $resultId) {
            return $data;
        }
    }

    return null;
}

function jlpt_ai_find_group_in_roadmap(array $roadmapData, $skillKey, $topic)
{
    foreach (($roadmapData['mistake_groups'] ?? []) as $group) {
        if (($group['skill_key'] ?? '') === $skillKey && ($group['topic'] ?? '') === $topic) {
            return $group;
        }
    }

    return null;
}

function jlpt_ai_generate_roadmap(PDO $pdo, $userId, $resultId = null)
{
    $resultId = $resultId ?: jlpt_ai_get_latest_result_id($pdo, $userId);
    if (!$resultId) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT er.id, er.user_id, er.score, er.total_correct, er.total_incorrect, er.exam_date,
                q.level, q.category, q.sub_tag, q.content, q.correct_answer, q.explanation,
                q.option_a, q.option_b, q.option_c, q.option_d,
                rd.question_id, rd.user_answer, rd.is_correct
         FROM exam_results er
         JOIN result_details rd ON rd.result_id = er.id
         JOIN questions q ON q.id = rd.question_id
         WHERE er.user_id = ? AND er.id = ?
         ORDER BY rd.id ASC"
    );
    $stmt->execute([$userId, $resultId]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return null;
    }

    $exam = $rows[0];
    $level = $exam['level'] ?: 'N3';
    $mistakeGroups = [];
    $wrongQuestions = [];

    foreach ($rows as $row) {
        if ((int) $row['is_correct'] === 1) {
            continue;
        }

        $skillKey = jlpt_ai_detect_skill((string) $row['category'], (string) $row['sub_tag']);
        $groupKey = $skillKey . '|' . trim((string) $row['sub_tag']);

        $stmtRand = $pdo->prepare("SELECT content, option_a, option_b, option_c, option_d, correct_answer FROM questions WHERE category = ? AND sub_tag = ? AND level = ? AND id != ? ORDER BY RAND() LIMIT 1");
        $stmtRand->execute([$row['category'], $row['sub_tag'], $row['level'], $row['question_id']]);
        $similarQ = $stmtRand->fetch(PDO::FETCH_ASSOC);

        if (!isset($mistakeGroups[$groupKey])) {
            $meta = jlpt_ai_skill_meta($skillKey);
            $mistakeGroups[$groupKey] = [
                'skill_key' => $skillKey,
                'skill_label' => $meta['label'],
                'topic' => trim((string) $row['sub_tag']) !== '' ? trim((string) $row['sub_tag']) : $meta['label'],
                'count' => 0,
                'summary' => $meta['summary'],
                'mistakes' => [],
            ];
        }

        $mistakeGroups[$groupKey]['count']++;
        $mistakesEntry = [
            'question_id' => (int) $row['question_id'],
            'question' => $row['content'],
            'user_answer' => $row['user_answer'],
            'correct_answer' => $row['correct_answer'],
            'explanation' => $row['explanation'],
            'option_a' => $row['option_a'],
            'option_b' => $row['option_b'],
            'option_c' => $row['option_c'],
            'option_d' => $row['option_d'],
        ];
        if ($similarQ) {
            $mistakesEntry['similar_question'] = $similarQ;
        }
        $mistakeGroups[$groupKey]['mistakes'][] = $mistakesEntry;

        $wrongQuestions[] = (int) $row['question_id'];
    }

    uasort($mistakeGroups, function ($a, $b) {
        if ($a['count'] === $b['count']) {
            return strcmp($a['topic'], $b['topic']);
        }

        return $b['count'] <=> $a['count'];
    });

    $mistakeGroups = array_values($mistakeGroups);
    foreach ($mistakeGroups as $index => &$group) {
        $group['resources'] = jlpt_ai_find_resources($pdo, (int) $exam['id'], $level, $group['skill_key'], $group['topic']);
        $group['status'] = $index === 0 ? 'Đang học' : 'Chưa bắt đầu';
    }
    unset($group);

    $steps = [];
    $steps[] = [
        'title' => 'Phân tích bài thi JLPT ' . $level,
        'status' => 'Hoàn thành',
        'desc' => 'Bài thi gần nhất đạt ' . rtrim(rtrim(number_format((float) $exam['score'], 2, '.', ''), '0'), '.') . '/100 với ' . (int) $exam['total_incorrect'] . ' câu sai.',
        'detail' => [
            'headline' => 'AI đã hoàn thành phân tích toàn bộ bài làm vừa nộp.',
            'mistake_count' => count($mistakeGroups),
            'resources' => [
                jlpt_ai_build_download_resource(
                    (int) $exam['id'],
                    'exam',
                    'Hồ sơ ôn tập tổng hợp cho toàn bài thi',
                    'Trang HTML tổng hợp tất cả nhóm lỗi, có bài tập sát từng câu sai và đáp án ẩn/hiện.'
                ),
            ],
        ],
    ];

    foreach (array_slice($mistakeGroups, 0, 3) as $groupIndex => $group) {
        $meta = jlpt_ai_skill_meta($group['skill_key']);
        $steps[] = [
            'title' => $meta['title_prefix'] . ' ' . $group['topic'],
            'status' => $groupIndex === 0 ? 'Đang học' : 'Chưa bắt đầu',
            'desc' => 'Sai ' . $group['count'] . ' câu thuộc nhóm ' . $group['skill_label'] . '. ' . $group['summary'],
            'detail' => [
                'headline' => 'Các lỗi nổi bật: ' . $group['skill_label'] . ' - ' . $group['topic'],
                'mistake_count' => $group['count'],
                'resources' => $group['resources'],
                'mistakes' => $group['mistakes'],
            ],
        ];
    }

    $steps[] = [
        'title' => 'Luyện đề tổng hợp',
        'status' => 'Chưa bắt đầu',
        'desc' => 'Làm lại một đề ' . $level . ' mới sau khi hoàn thành các mục ôn tập để kiểm tra tiến bộ.',
        'detail' => [
            'headline' => 'Bước xác nhận năng lực sau ôn tập.',
            'mistake_count' => 0,
            'resources' => [
                [
                    'id' => 'retry_quiz',
                    'title' => 'Làm thêm đề JLPT ' . $level,
                    'description' => 'Quay lại khu luyện thi để kiểm tra mức độ cải thiện sau khi ôn tập.',
                    'url' => 'quiz.php?level=' . urlencode($level),
                    'download' => false,
                ],
            ],
        ],
    ];

    return [
        'generated_from_result_id' => (int) $exam['id'],
        'generated_at' => date('c'),
        'level' => $level,
        'summary' => [
            'score' => (float) $exam['score'],
            'total_correct' => (int) $exam['total_correct'],
            'total_incorrect' => (int) $exam['total_incorrect'],
            'exam_date' => $exam['exam_date'],
            'wrong_question_ids' => $wrongQuestions,
        ],
        'mistake_groups' => $mistakeGroups,
        'steps' => $steps,
    ];
}

function jlpt_ai_save_roadmap(PDO $pdo, $userId, array $roadmapData)
{
    $stmt = $pdo->prepare("INSERT INTO ai_roadmaps (user_id, roadmap_content, status, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $userId,
        json_encode($roadmapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'Đang học',
    ]);

    return (int) $pdo->lastInsertId();
}

function jlpt_ai_generate_and_save_roadmap(PDO $pdo, $userId, $resultId = null)
{
    $roadmapData = jlpt_ai_generate_roadmap($pdo, $userId, $resultId);
    if (!$roadmapData) {
        return null;
    }

    jlpt_ai_save_roadmap($pdo, $userId, $roadmapData);

    return $roadmapData;
}
