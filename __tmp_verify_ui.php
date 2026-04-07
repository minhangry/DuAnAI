<?php
require 'view/db.php';
require 'view/ai_recommendation_service.php';
$roadmap = jlpt_ai_generate_roadmap($pdo, 4, 4);
$group = jlpt_ai_find_group_in_roadmap($roadmap, 'kanji', 'Cách đọc Kanji');
$html = jlpt_ai_render_personalized_html(jlpt_ai_build_personalized_doc_data($group, $roadmap));
foreach (['In / PDF','Chấm ngay','Hiện đáp án/gợi ý chấm','textarea class="exercise-input"'] as $needle) {
    echo (strpos($html, $needle) !== false ? 'FOUND: ' : 'MISS: ') . $needle . PHP_EOL;
}
