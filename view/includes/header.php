<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JLPT AI Learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d9488;
            --primary-gradient: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
            --accent-color: #f97316;
            --text-main: #0f172a;
            --bg-body: #f1f5f9;
        }
        body { 
            font-family: 'Inter', 'Noto Sans JP', sans-serif; 
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
        }
        .navbar { 
            background: var(--primary-gradient) !important;
            box-shadow: 0 2px 15px rgba(13, 148, 136, 0.15);
            border-bottom: 3px solid var(--accent-color);
            padding: 0.8rem 0;
        }
        .navbar-brand { 
            font-weight: 800; 
            letter-spacing: -0.5px;
            color: #ffffff !important; 
        }
        .nav-link { 
            font-weight: 500; 
            color: #e0f2fe !important; 
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover { 
            color: #ffffff !important; 
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .btn-primary { 
            background: var(--primary-gradient);
            border: none; 
            border-radius: 10px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            box-shadow: 0 4px 6px rgba(13, 148, 136, 0.2);
        }
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 148, 136, 0.3);
        }
        .card { 
            border-radius: 16px; 
            border: none; 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 16px rgba(0,0,0,.15);
        }
        .text-primary { color: var(--primary-color) !important; }
        .badge-accent { background-color: var(--accent-color); color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">JLPT AI</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold" href="profile.php"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quiz.php">Luyện thi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="roadmap.php">Lộ trình</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grammar.php">Thư viện Ngữ pháp</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="flashcard.php">Thẻ ghi nhớ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link" href="dangky.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>