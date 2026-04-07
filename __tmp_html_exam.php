<?php
require 'view/db.php';
require 'view/ai_recommendation_service.php';
$roadmap = jlpt_ai_generate_roadmap($pdo, 4, 4);
$html = jlpt_ai_render_personalized_html(jlpt_ai_build_exam_doc_data($roadmap));
echo substr($html, 0, 1200);
