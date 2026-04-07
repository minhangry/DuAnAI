-- Tạo cơ sở dữ liệu với utf8mb4 để hỗ trợ tốt tiếng Nhật và tiếng Việt
CREATE DATABASE IF NOT EXISTS jlpt_ai_learning 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE jlpt_ai_learning;

-- ==========================================
-- 1. Bảng users: Lưu trữ thông tin người dùng
-- ==========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính của người dùng',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập, không được trùng',
    password VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã được mã hóa (Hash)',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email liên hệ',
    current_level ENUM('N5', 'N4', 'N3', 'N2', 'Beginner') NOT NULL DEFAULT 'Beginner' COMMENT 'Trình độ hiện tại',
    target_level ENUM('N5', 'N4', 'N3', 'N2') NOT NULL COMMENT 'Trình độ mục tiêu JLPT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày đăng ký tài khoản'
) ENGINE=InnoDB COMMENT='Bảng lưu trữ thông tin học viên';

-- ==========================================
-- 2. Bảng questions: Ngân hàng câu hỏi
-- ==========================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính câu hỏi',
    level ENUM('N5', 'N4', 'N3', 'N2') NOT NULL COMMENT 'Cấp độ JLPT của câu hỏi',
    category VARCHAR(50) NOT NULL COMMENT 'Phân loại: Từ vựng, Ngữ pháp, Hán tự, Đọc hiểu',
    sub_tag VARCHAR(100) NOT NULL COMMENT 'Thẻ phụ dùng cho AI (VD: Trợ từ, Kính ngữ, Thể bị động)',
    content TEXT NOT NULL COMMENT 'Nội dung câu hỏi',
    option_a TEXT NOT NULL COMMENT 'Nội dung đáp án A',
    option_b TEXT NOT NULL COMMENT 'Nội dung đáp án B',
    option_c TEXT NOT NULL COMMENT 'Nội dung đáp án C',
    option_d TEXT NOT NULL COMMENT 'Nội dung đáp án D',
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL COMMENT 'Đáp án đúng',
    explanation TEXT COMMENT 'Giải thích chi tiết tại sao đúng/sai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Bảng lưu trữ ngân hàng câu hỏi để thi và luyện tập';

-- ==========================================
-- 3. Bảng grammar_lessons: Bài học ngữ pháp
-- ==========================================
CREATE TABLE grammar_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính bài học ngữ pháp',
    structure_name VARCHAR(255) NOT NULL COMMENT 'Tên cấu trúc ngữ pháp (VD: ～に違いない)',
    meaning TEXT NOT NULL COMMENT 'Ý nghĩa của cấu trúc',
    usage_rules TEXT NOT NULL COMMENT 'Cách dùng, hoàn cảnh sử dụng',
    examples JSON COMMENT 'Lưu mảng JSON chứa các ví dụ (Nhật - Việt) để dễ dàng thao tác',
    level ENUM('N5', 'N4', 'N3', 'N2') NOT NULL COMMENT 'Cấp độ JLPT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Bảng lưu dữ liệu bài học ngữ pháp từ file JSON';

-- ==========================================
-- 4. Bảng exam_results: Lịch sử thi của người dùng
-- ==========================================
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính lịch sử thi',
    user_id INT NOT NULL COMMENT 'Khóa ngoại liên kết tới người dùng',
    score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Điểm số đạt được',
    total_correct INT DEFAULT 0 COMMENT 'Tổng số câu trả lời đúng',
    total_incorrect INT DEFAULT 0 COMMENT 'Tổng số câu trả lời sai',
    exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian nộp bài thi',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Bảng lưu tổng quan kết quả các bài thi/kiểm tra';

-- ==========================================
-- 5. Bảng result_details: Chi tiết từng câu hỏi trong bài thi
-- ==========================================
CREATE TABLE result_details (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính chi tiết kết quả',
    result_id INT NOT NULL COMMENT 'Khóa ngoại liên kết tới bài thi (exam_results)',
    question_id INT NOT NULL COMMENT 'Khóa ngoại liên kết tới câu hỏi (questions)',
    user_answer ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Đáp án người dùng chọn (NULL nếu bỏ qua)',
    is_correct BOOLEAN NOT NULL COMMENT 'Trạng thái: 1 = Đúng, 0 = Sai',
    FOREIGN KEY (result_id) REFERENCES exam_results(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Dữ liệu chi tiết từng câu dùng để AI phân tích lỗ hổng kiến thức';

-- ==========================================
-- 6. Bảng ai_roadmaps: Lộ trình học AI đề xuất
-- ==========================================
CREATE TABLE ai_roadmaps (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính lộ trình',
    user_id INT NOT NULL COMMENT 'Khóa ngoại liên kết tới người dùng',
    roadmap_content JSON NOT NULL COMMENT 'Lộ trình AI trả về dạng JSON để Render dễ dàng lên Frontend',
    status ENUM('Chưa bắt đầu', 'Đang học', 'Hoàn thành') DEFAULT 'Chưa bắt đầu' COMMENT 'Trạng thái của lộ trình',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày AI tạo lộ trình',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Lưu trữ lộ trình cá nhân hóa do AI phân tích và đề xuất';
-- ==========================================
-- 7. Bảng flashcard_topics: Chủ đề Flashcard
-- ==========================================
CREATE TABLE IF NOT EXISTS flashcard_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên chủ đề (vd: Gia đình, Công việc)',
    level ENUM('N5', 'N4', 'N3', 'N2', 'N1') NOT NULL COMMENT 'Cấp độ JLPT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Bảng chứa danh sách các chủ đề từ vựng';

-- ==========================================
-- 8. Bảng flashcard_words: Từ vựng thuộc Chủ đề
-- ==========================================
CREATE TABLE IF NOT EXISTS flashcard_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL COMMENT 'Khóa ngoại liên kết tới flashcard_topics',
    word VARCHAR(100) NOT NULL COMMENT 'Từ vựng tiếng Nhật (Kanji/Kana)',
    reading VARCHAR(100) NOT NULL COMMENT 'Cách đọc (Hiragana/Katakana)',
    meaning TEXT NOT NULL COMMENT 'Ý nghĩa tiếng Việt',
    example TEXT NULL COMMENT 'Câu ví dụ (nếu có)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES flashcard_topics(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Bảng chứa các từ vựng chi tiết cho từng chủ đề';

