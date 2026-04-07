<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
require_once 'ai_recommendation_service.php';

$userId = $_SESSION['user_id'];
$resultId = isset($_GET['result_id']) ? (int) $_GET['result_id'] : 0;
$mode = trim($_GET['mode'] ?? 'group');
$skill = trim($_GET['skill'] ?? '');
$topic = trim($_GET['topic'] ?? '');

if ($resultId <= 0) {
    http_response_code(400);
    exit('Thiếu result_id để tạo tài liệu cá nhân hóa.');
}

$roadmapData = jlpt_ai_get_roadmap_by_result($pdo, $userId, $resultId);
if (!$roadmapData) {
    $roadmapData = jlpt_ai_generate_roadmap($pdo, $userId, $resultId);
}

if (!$roadmapData) {
    http_response_code(404);
    exit('Không tìm thấy dữ liệu lộ trình cho bài thi này.');
}

if ($mode === 'exam') {
    $docData = jlpt_ai_build_exam_doc_data($pdo, $roadmapData);
    $filename = 'ho-so-on-tap-jlpt-' . $resultId . '.html';
} else {
    if ($skill === '' || $topic === '') {
        http_response_code(400);
        exit('Thiếu skill hoặc topic để tạo tài liệu theo nhóm lỗi.');
    }

    $group = jlpt_ai_find_group_in_roadmap($roadmapData, $skill, $topic);
    if (!$group) {
        http_response_code(404);
        exit('Không tìm thấy nhóm lỗi phù hợp để tạo tài liệu.');
    }

    $docData = jlpt_ai_build_personalized_doc_data($pdo, $group, $roadmapData);
    $filename = 'tai-lieu-ca-nhan-hoa-' . $resultId . '-' . preg_replace('/[^a-z0-9\-]+/i', '-', $skill) . '.html';
}

$content = jlpt_ai_render_personalized_html($docData);

header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
echo $content;
