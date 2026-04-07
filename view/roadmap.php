<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
require_once 'ai_recommendation_service.php';

$user_id = $_SESSION['user_id'];
$refreshRequested = isset($_GET['refresh']) && $_GET['refresh'] === '1';

if ($refreshRequested) {
    jlpt_ai_generate_and_save_roadmap($pdo, $user_id);
    header('Location: roadmap.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ai_roadmaps WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$roadmap = $stmt->fetch();

if (!$roadmap) {
    $generated = jlpt_ai_generate_and_save_roadmap($pdo, $user_id);
    if ($generated) {
        $stmt->execute([$user_id]);
        $roadmap = $stmt->fetch();
    }
}

$roadmapContent = $roadmap ? json_decode($roadmap['roadmap_content'], true) : null;
$steps = $roadmapContent['steps'] ?? [];
$summary = $roadmapContent['summary'] ?? null;
$mistakeGroups = $roadmapContent['mistake_groups'] ?? [];
$level = $roadmapContent['level'] ?? 'N3';

if (empty($steps)) {
    $steps = [[
        'title' => 'Chưa có dữ liệu lộ trình',
        'status' => 'Chưa bắt đầu',
        'desc' => 'Hãy làm ít nhất một đề JLPT để hệ thống phân tích lỗi sai và đề xuất tài liệu học tập.',
        'detail' => [
            'headline' => 'Lộ trình sẽ xuất hiện sau khi bạn hoàn thành bài thi.',
            'resources' => [[
                'title' => 'Bắt đầu làm đề JLPT',
                'description' => 'Đi đến trang luyện thi để hệ thống lấy dữ liệu phân tích.',
                'url' => 'quiz.php',
                'download' => false,
            ]],
        ],
    ]];
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .roadmap-wrapper { max-width: 980px; margin: 0 auto; }
    .roadmap-container { position: relative; padding-left: 44px; }
    .roadmap-container::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #dbe7f1;
        border-radius: 999px;
    }
    .roadmap-step { position: relative; margin-bottom: 32px; }
    .step-dot {
        position: absolute;
        left: -38px;
        top: 10px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 0 0 4px #dbe7f1;
        z-index: 2;
    }
    .status-done { background-color: #10b981; box-shadow: 0 0 0 4px #d1fae5; }
    .status-doing { background-color: #3b82f6; box-shadow: 0 0 0 4px #dbeafe; }
    .status-todo { background-color: #cbd5e1; }
    .roadmap-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        border-left: 5px solid #94a3b8;
        overflow: hidden;
    }
    .roadmap-card.done { border-left-color: #10b981; }
    .roadmap-card.doing { border-left-color: #3b82f6; }
    .roadmap-card.todo { border-left-color: #64748b; }
    .roadmap-card:hover { transform: translateX(8px); transition: transform 0.25s ease; }
    .status-pill {
        font-size: 0.9rem;
        border-radius: 999px;
        padding: 8px 18px;
        font-weight: 700;
    }
    .pill-done { background: #d1fae5; color: #047857; }
    .pill-doing { background: #dbeafe; color: #1d4ed8; }
    .pill-todo { background: #e5e7eb; color: #6b7280; }
    .summary-card {
        border: 0;
        border-radius: 28px;
        background: linear-gradient(135deg, rgba(13,148,136,0.12), rgba(2,132,199,0.08));
        box-shadow: 0 18px 40px rgba(13, 148, 136, 0.08);
    }
    .mini-stat { background: rgba(255,255,255,0.85); border-radius: 18px; padding: 18px; height: 100%; }
    .detail-panel { background: #f8fafc; border-top: 1px solid #e2e8f0; }
    .detail-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; height: 100%; }
    .resource-link { text-decoration: none; color: inherit; display: block; }
    .resource-link:hover .detail-box { border-color: var(--primary-color); box-shadow: 0 10px 24px rgba(13, 148, 136, 0.08); }
    .resource-action { cursor: pointer; }
    .mistake-item { background: #ffffff; border: 1px solid #fee2e2; border-left: 4px solid #ef4444; border-radius: 14px; padding: 14px 16px; margin-bottom: 12px; }
    .resource-modal-frame { width: 100%; height: 70vh; border: 0; border-radius: 18px; background: #fff; }
</style>

<div class="container py-5">
    <div class="roadmap-wrapper">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary"><i class="bi bi-magic"></i> Lộ trình AI đề xuất</h2>
            <p class="text-muted mb-0">Dựa trên lỗi sai ở bài JLPT gần nhất, hệ thống gợi ý lộ trình học và tài liệu phù hợp cho bạn.</p>
        </div>

        <?php if ($summary): ?>
            <div class="card summary-card mb-5">
                <div class="card-body p-4 p-md-5">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-5">
                            <h4 class="fw-bold mb-2">Tổng quan bài thi JLPT <?php echo htmlspecialchars($level); ?></h4>
                            <p class="text-muted mb-0">AI đã phân tích bài thi gần nhất và gom các lỗi sai thành nhóm để bạn ôn đúng trọng tâm.</p>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-sm-4"><div class="mini-stat text-center"><div class="small text-muted text-uppercase fw-bold">Điểm</div><div class="display-6 fw-bold text-primary"><?php echo htmlspecialchars((string) $summary['score']); ?></div></div></div>
                                <div class="col-sm-4"><div class="mini-stat text-center"><div class="small text-muted text-uppercase fw-bold">Câu sai</div><div class="display-6 fw-bold text-danger"><?php echo (int) $summary['total_incorrect']; ?></div></div></div>
                                <div class="col-sm-4"><div class="mini-stat text-center"><div class="small text-muted text-uppercase fw-bold">Nhóm lỗi</div><div class="display-6 fw-bold text-success"><?php echo count($mistakeGroups); ?></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="roadmap-container">
            <?php foreach ($steps as $index => $step): ?>
                <?php
                $status = $step['status'] ?? 'Chưa bắt đầu';
                $dotClass = 'status-todo';
                $cardClass = 'todo';
                $pillClass = 'pill-todo';
                if ($status === 'Hoàn thành') {
                    $dotClass = 'status-done';
                    $cardClass = 'done';
                    $pillClass = 'pill-done';
                } elseif ($status === 'Đang học') {
                    $dotClass = 'status-doing';
                    $cardClass = 'doing';
                    $pillClass = 'pill-doing';
                }
                $collapseId = 'roadmapDetail' . $index;
                $detail = $step['detail'] ?? [];
                $resources = $detail['resources'] ?? [];
                $mistakes = $detail['mistakes'] ?? [];
                ?>
                <div class="roadmap-step" data-aos="fade-left">
                    <div class="step-dot <?php echo $dotClass; ?>"></div>
                    <div class="roadmap-card <?php echo $cardClass; ?>">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-2">
                                <div>
                                    <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($step['title']); ?></h3>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($step['desc']); ?></p>
                                </div>
                                <span class="status-pill <?php echo $pillClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </div>

                            <?php if (!empty($resources)): ?>
                                <div class="mt-4 d-flex flex-wrap gap-3 align-items-center">
                                    <button class="btn btn-outline-primary rounded-pill px-4" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">Xem chi tiết</button>
                                    <?php $primaryResource = $resources[0]; ?>
                                    <?php if (!empty($primaryResource['format']) && $primaryResource['format'] === 'html'): ?>
                                        <button type="button" class="btn btn-primary rounded-pill px-4 open-resource-modal" data-resource-url="<?php echo htmlspecialchars($primaryResource['url']); ?>" data-resource-title="<?php echo htmlspecialchars($primaryResource['title']); ?>">Mở tài liệu</button>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($primaryResource['url']); ?>" class="btn btn-primary rounded-pill px-4" <?php echo !empty($primaryResource['download']) ? 'download' : ''; ?>><?php echo !empty($primaryResource['download']) ? 'Tải tài liệu' : 'Mở tài liệu'; ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="collapse" id="<?php echo $collapseId; ?>">
                            <div class="detail-panel p-4 p-md-5 pt-0">
                                <?php if (!empty($detail['headline'])): ?>
                                    <div class="pt-3 pb-2 fw-semibold text-primary"><?php echo htmlspecialchars($detail['headline']); ?></div>
                                <?php endif; ?>

                                <?php if (!empty($mistakes)): ?>
                                    <div class="mb-4">
                                        <?php foreach ($mistakes as $mistake): ?>
                                            <div class="mistake-item">
                                                <div class="fw-bold mb-2">Câu hỏi lỗi #<?php echo (int) $mistake['question_id']; ?></div>
                                                <div class="mb-2"><?php echo htmlspecialchars($mistake['question']); ?></div>
                                                <div class="small text-muted mb-1">Bạn chọn: <?php echo htmlspecialchars((string) $mistake['user_answer']); ?> | Đáp án đúng: <?php echo htmlspecialchars((string) $mistake['correct_answer']); ?></div>
                                                <?php if (!empty($mistake['explanation'])): ?><div class="small text-muted">Giải thích: <?php echo htmlspecialchars($mistake['explanation']); ?></div><?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($resources)): ?>
                                    <div class="row g-3">
                                        <?php foreach ($resources as $resource): ?>
                                            <div class="col-md-6 col-xl-4">
                                                <?php if (!empty($resource['format']) && $resource['format'] === 'html'): ?>
                                                    <div class="resource-link resource-action open-resource-modal" data-resource-url="<?php echo htmlspecialchars($resource['url']); ?>" data-resource-title="<?php echo htmlspecialchars($resource['title']); ?>">
                                                        <div class="detail-box">
                                                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($resource['title']); ?></h6>
                                                                <span class="badge bg-primary-subtle text-primary">Mở</span>
                                                            </div>
                                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <a class="resource-link" href="<?php echo htmlspecialchars($resource['url']); ?>" <?php echo !empty($resource['download']) ? 'download' : ''; ?>>
                                                        <div class="detail-box">
                                                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($resource['title']); ?></h6>
                                                                <span class="badge <?php echo !empty($resource['download']) ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'; ?>"><?php echo !empty($resource['download']) ? 'Tải về' : 'Mở'; ?></span>
                                                            </div>
                                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                        </div>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="roadmap.php?refresh=1" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm"><i class="bi bi-arrow-repeat me-2"></i> Yêu cầu AI cập nhật lộ trình mới</a>
        </div>
    </div>
</div>

<div class="modal fade" id="resourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="resourceModalTitle">Tài liệu cá nhân hóa</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-pill btn-sm" id="resourcePrintBtn">In / PDF</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body pt-0">
                <iframe id="resourceModalFrame" class="resource-modal-frame" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('resourceModal');
    var modal = new bootstrap.Modal(modalElement);
    var frame = document.getElementById('resourceModalFrame');
    var title = document.getElementById('resourceModalTitle');
    var printBtn = document.getElementById('resourcePrintBtn');

    document.querySelectorAll('.open-resource-modal').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            frame.src = trigger.getAttribute('data-resource-url');
            title.textContent = trigger.getAttribute('data-resource-title') || 'Tài liệu cá nhân hóa';
            modal.show();
        });
    });

    printBtn.addEventListener('click', function () {
        if (frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        frame.src = 'about:blank';
    });
});
</script>
