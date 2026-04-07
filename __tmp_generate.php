<?php
require 'view/db.php';
require 'view/ai_recommendation_service.php';
$data = jlpt_ai_generate_and_save_roadmap($pdo, 4, 4);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
