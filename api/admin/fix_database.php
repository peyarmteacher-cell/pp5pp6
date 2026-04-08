<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Super Admin เท่านั้นที่สามารถรันสคริปต์นี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$results = [];

try {
    // 1. ตรวจสอบและเพิ่มคอลัมน์ is_academic ในตาราง users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_academic'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_academic BOOLEAN DEFAULT FALSE AFTER role");
        $results[] = "เพิ่มคอลัมน์ is_academic ในตาราง users สำเร็จ";
    } else {
        $results[] = "ตาราง users มีคอลัมน์ is_academic อยู่แล้ว";
    }

    // 2. ตรวจสอบและเพิ่มคอลัมน์ national_id ในตาราง students
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'national_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN national_id VARCHAR(13) NOT NULL AFTER student_code");
        $results[] = "เพิ่มคอลัมน์ national_id ในตาราง students สำเร็จ";
    } else {
        $results[] = "ตาราง students มีคอลัมน์ national_id อยู่แล้ว";
    }

    // 3. ตรวจสอบและเพิ่มคอลัมน์ hours ในตาราง subjects
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'hours'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE subjects ADD COLUMN hours INT DEFAULT 40 AFTER level");
        $results[] = "เพิ่มคอลัมน์ hours ในตาราง subjects สำเร็จ";
    } else {
        $results[] = "ตาราง subjects มีคอลัมน์ hours อยู่แล้ว";
    }

    // 4. ตรวจสอบและเพิ่มคอลัมน์ credits ในตาราง subjects
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'credits'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE subjects ADD COLUMN credits FLOAT DEFAULT 1.0 AFTER hours");
        $results[] = "เพิ่มคอลัมน์ credits ในตาราง subjects สำเร็จ";
    } else {
        $results[] = "ตาราง subjects มีคอลัมน์ credits อยู่แล้ว";
    }

    // 5. เพิ่มตาราง classrooms
    $pdo->exec("CREATE TABLE IF NOT EXISTS classrooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        level VARCHAR(50) NOT NULL,
        room VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
        UNIQUE KEY unique_classroom (school_id, level, room)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง classrooms สำเร็จ";

    // 6. เพิ่มคอลัมน์ room และ classroom_id ในตาราง students
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'room'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN room VARCHAR(10) AFTER level");
        $results[] = "เพิ่มคอลัมน์ room ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN classroom_id INT AFTER room");
        $pdo->exec("ALTER TABLE students ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL");
        $results[] = "เพิ่มคอลัมน์ classroom_id และ Foreign Key ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'academic_year'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN academic_year VARCHAR(4) DEFAULT '2567' AFTER school_id");
        $results[] = "เพิ่มคอลัมน์ academic_year ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'prefix'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN prefix VARCHAR(20) AFTER student_code");
        $results[] = "เพิ่มคอลัมน์ prefix ในตาราง students สำเร็จ";
    }

    // ซิงค์ classroom_id ให้กับนักเรียนที่ยังไม่มี (อิงตาม level และ room)
    $stmt = $pdo->query("SELECT year FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_row = $stmt->fetch();
    $current_year = $current_year_row ? $current_year_row['year'] : '2567';
    
    $pdo->prepare("UPDATE students SET academic_year = ? WHERE academic_year IS NULL OR academic_year = ''")->execute([$current_year]);
    
    // ซิงค์แบบปกติ
    $stmt_sync = $pdo->exec("UPDATE students s 
                JOIN classrooms c ON s.school_id = c.school_id AND s.level = c.level AND s.room = c.room
                SET s.classroom_id = c.id
                WHERE s.classroom_id IS NULL OR s.classroom_id = 0");

    // ซิงค์แบบล้าง prefix (เช่น ป.1 -> 1, ม.1 -> 1)
    $stmt_sync_clean = $pdo->exec("UPDATE students s 
                JOIN classrooms c ON s.school_id = c.school_id AND REPLACE(REPLACE(s.level, 'ป.', ''), 'ม.', '') = c.level AND s.room = c.room
                SET s.classroom_id = c.id
                WHERE s.classroom_id IS NULL OR s.classroom_id = 0");

    $results[] = "ซิงค์ข้อมูลห้องเรียนให้นักเรียนสำเร็จ (ปกติ: $stmt_sync, ล้าง prefix: $stmt_sync_clean) และตั้งปีการศึกษาเป็น $current_year";

    // แก้ไข teacher_assignments ที่ไม่มี classroom_id (ขยายให้ครบทุกห้องในระดับชั้นนั้น)
    $stmt = $pdo->query("SELECT ta.*, s.level, s.school_id FROM teacher_assignments ta JOIN subjects s ON ta.subject_id = s.id WHERE ta.classroom_id IS NULL OR ta.classroom_id = 0");
    $null_assignments = $stmt->fetchAll();
    if (count($null_assignments) > 0) {
        $pdo->beginTransaction();
        foreach ($null_assignments as $ta) {
            // หาห้องเรียนทั้งหมดในระดับชั้นนั้น
            $stmt_rooms = $pdo->prepare("SELECT id FROM classrooms WHERE level = ? AND school_id = ?");
            $stmt_rooms->execute([$ta['level'], $ta['school_id']]);
            $rooms = $stmt_rooms->fetchAll();
            
            foreach ($rooms as $r) {
                // ตรวจสอบว่ามีอยู่แล้วหรือไม่
                $stmt_check = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?");
                $stmt_check->execute([$ta['teacher_id'], $ta['subject_id'], $r['id'], $ta['academic_year'], $ta['semester']]);
                if (!$stmt_check->fetch()) {
                    $stmt_ins = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, classroom_id, academic_year, semester) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$ta['teacher_id'], $ta['subject_id'], $r['id'], $ta['academic_year'], $ta['semester']]);
                }
            }
            // ลบตัวที่ไม่มี classroom_id หรือ classroom_id = 0 ออก
            $pdo->prepare("DELETE FROM teacher_assignments WHERE id = ?")->execute([$ta['id']]);
        }
        $pdo->commit();
        $results[] = "ขยายงานสอนที่ไม่มีห้องเรียนให้ครบทุกห้องสำเร็จ (" . count($null_assignments) . " รายการ)";
    }

    // 7. ตรวจสอบและเพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments
    $stmt = $pdo->query("SHOW COLUMNS FROM teacher_assignments LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE teacher_assignments ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE teacher_assignments ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
        $results[] = "เพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments สำเร็จ";
    }

    // 8. เพิ่มตารางบันทึกคะแนน
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        score_midterm FLOAT DEFAULT 0,
        score_final FLOAT DEFAULT 0,
        score_total FLOAT DEFAULT 0,
        grade VARCHAR(5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_grade (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ classroom_id ใน grades หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE grades ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
        $results[] = "เพิ่มคอลัมน์ classroom_id ในตาราง grades สำเร็จ";
    }

    // ตรวจสอบและเพิ่มคอลัมน์ teacher_id ใน grades หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'teacher_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN teacher_id INT AFTER classroom_id");
        $pdo->exec("ALTER TABLE grades ADD FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE");
        $results[] = "เพิ่มคอลัมน์ teacher_id ในตาราง grades สำเร็จ";
    }

    // ตรวจสอบและเพิ่มคอลัมน์ score_units ใน grades หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_units'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_units FLOAT DEFAULT 0 AFTER semester");
        $results[] = "เพิ่มคอลัมน์ score_units ในตาราง grades สำเร็จ";
    }

    // ตรวจสอบและเพิ่มคอลัมน์ score_percent ใน grades หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_percent'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_percent FLOAT DEFAULT 0 AFTER score_total");
        $results[] = "เพิ่มคอลัมน์ score_percent ในตาราง grades สำเร็จ";
    }
    
    $results[] = "ตรวจสอบ/สร้างตาราง grades สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS characteristics_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        item1 INT DEFAULT 0,
        item2 INT DEFAULT 0,
        item3 INT DEFAULT 0,
        item4 INT DEFAULT 0,
        item5 INT DEFAULT 0,
        item6 INT DEFAULT 0,
        item7 INT DEFAULT 0,
        item8 INT DEFAULT 0,
        average_score FLOAT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_char (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง characteristics_scores สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS analytical_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        item1 INT DEFAULT 0,
        item2 INT DEFAULT 0,
        item3 INT DEFAULT 0,
        item4 INT DEFAULT 0,
        item5 INT DEFAULT 0,
        average_score FLOAT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_analytical (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure columns exist if table was already created
    $stmt = $pdo->query("SHOW COLUMNS FROM analytical_scores LIKE 'item1'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE analytical_scores 
            ADD COLUMN item1 INT DEFAULT 0 AFTER semester,
            ADD COLUMN item2 INT DEFAULT 0 AFTER item1,
            ADD COLUMN item3 INT DEFAULT 0 AFTER item2,
            ADD COLUMN item4 INT DEFAULT 0 AFTER item3,
            ADD COLUMN item5 INT DEFAULT 0 AFTER item4,
            ADD COLUMN average_score FLOAT DEFAULT 0 AFTER item5
        ");
    }
    $results[] = "ตรวจสอบ/สร้างตาราง analytical_scores สำเร็จ";

    // 9. ปรับปรุงตาราง learning_units และเพิ่ม unit_scores
    $pdo->exec("CREATE TABLE IF NOT EXISTS learning_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_id INT,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        unit_name VARCHAR(255) NOT NULL,
        max_score FLOAT DEFAULT 10,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ classroom_id ใน learning_units หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE learning_units ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'academic_year'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN academic_year VARCHAR(4) AFTER classroom_id");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'semester'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN semester INT AFTER academic_year");
    }
    $results[] = "ตรวจสอบ/สร้างตาราง learning_units สำเร็จ";

    // ปรับปรุงตาราง grades ให้รองรับคะแนนหน่วยและร้อยละ
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_units'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_units FLOAT DEFAULT 0 AFTER semester");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_midterm'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_midterm FLOAT DEFAULT 0 AFTER score_units");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_final'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_final FLOAT DEFAULT 0 AFTER score_midterm");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_total'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_total FLOAT DEFAULT 0 AFTER score_final");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_percent'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_percent FLOAT DEFAULT 0 AFTER score_total");
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'grade'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN grade VARCHAR(5) AFTER score_percent");
    }
    $results[] = "ตรวจสอบ/ปรับปรุงตาราง grades สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        learning_unit_id INT,
        score FLOAT DEFAULT 0,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (learning_unit_id) REFERENCES learning_units(id) ON DELETE CASCADE,
        UNIQUE KEY unique_unit_score (student_id, learning_unit_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง unit_scores สำเร็จ";

    // 10. ปรับปรุงตาราง grades ให้รองรับคะแนนรายปี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_semester1'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_semester1 FLOAT DEFAULT 0 AFTER semester");
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_semester2 FLOAT DEFAULT 0 AFTER score_semester1");
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_annual_avg FLOAT DEFAULT 0 AFTER score_semester2");
        $results[] = "เพิ่มคอลัมน์คะแนนรายปีในตาราง grades สำเร็จ";
    }

    // 11. เพิ่มตาราง academic_years และคอลัมน์สถานะนักเรียน
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        year VARCHAR(4) NOT NULL,
        is_current BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
        UNIQUE KEY unique_year (school_id, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง academic_years สำเร็จ";

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('studying', 'graduated', 'transferred', 'quit') DEFAULT 'studying' AFTER academic_year");
        $results[] = "เพิ่มคอลัมน์ status ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'generation'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN generation VARCHAR(50) AFTER status");
        $results[] = "เพิ่มคอลัมน์ generation ในตาราง students สำเร็จ";
    }

    // 11.5 เพิ่มคอลัมน์สำหรับข้อมูล DMC
    $dmc_columns = [
        'last_name' => "VARCHAR(255) AFTER name",
        'gender' => "VARCHAR(10) AFTER student_code",
        'birthday' => "DATE AFTER last_name",
        'age' => "INT AFTER birthday",
        'weight' => "FLOAT AFTER age",
        'height' => "FLOAT AFTER weight",
        'blood_group' => "VARCHAR(5) AFTER height",
        'religion' => "VARCHAR(50) AFTER blood_group",
        'race' => "VARCHAR(50) AFTER religion",
        'nationality' => "VARCHAR(50) AFTER race",
        'house_no' => "VARCHAR(50) AFTER nationality",
        'moo' => "VARCHAR(10) AFTER house_no",
        'road_soi' => "VARCHAR(100) AFTER moo",
        'sub_district' => "VARCHAR(100) AFTER road_soi",
        'district' => "VARCHAR(100) AFTER sub_district",
        'province_name' => "VARCHAR(100) AFTER district",
        'parent_name' => "VARCHAR(255) AFTER province_name",
        'parent_last_name' => "VARCHAR(255) AFTER parent_name",
        'parent_occupation' => "VARCHAR(100) AFTER parent_last_name",
        'parent_relationship' => "VARCHAR(100) AFTER parent_occupation",
        'father_name' => "VARCHAR(255) AFTER parent_relationship",
        'father_last_name' => "VARCHAR(255) AFTER father_name",
        'father_occupation' => "VARCHAR(100) AFTER father_last_name",
        'mother_name' => "VARCHAR(255) AFTER father_occupation",
        'mother_last_name' => "VARCHAR(255) AFTER mother_name",
        'mother_occupation' => "VARCHAR(100) AFTER mother_last_name",
        'disadvantage' => "VARCHAR(255) AFTER mother_occupation"
    ];

    foreach ($dmc_columns as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE students ADD COLUMN $col $def");
            $results[] = "เพิ่มคอลัมน์ $col ในตาราง students สำเร็จ";
        }
    }

    // 12. เพิ่มตารางกิจกรรมพัฒนาผู้เรียน
    $pdo->exec("CREATE TABLE IF NOT EXISTS clubs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        name VARCHAR(255) NOT NULL,
        academic_year VARCHAR(4) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง clubs สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS learner_development_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        guidance_result ENUM('P', 'F', '') DEFAULT '',
        scout_result ENUM('P', 'F', '') DEFAULT '',
        club_id INT,
        club_result ENUM('P', 'F', '') DEFAULT '',
        social_result ENUM('P', 'F', '') DEFAULT '',
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_learner_dev (student_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ teacher_id ใน learner_development_results หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM learner_development_results LIKE 'teacher_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learner_development_results ADD COLUMN teacher_id INT AFTER social_result");
        $pdo->exec("ALTER TABLE learner_development_results ADD FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL");
        $results[] = "เพิ่มคอลัมน์ teacher_id ในตาราง learner_development_results สำเร็จ";
    }

    $results[] = "ตรวจสอบ/สร้างตาราง learner_development_results สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS learner_development_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        UNIQUE KEY unique_ld_assignment (teacher_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง learner_development_assignments สำเร็จ";
    
    // 13. เพิ่มตารางบันทึกน้ำหนักส่วนสูง
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_health_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        record_number INT,
        weight FLOAT,
        height FLOAT,
        recorded_date DATE,
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_health_record (student_id, academic_year, semester, record_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง student_health_records สำเร็จ";

    // 14. เพิ่มตารางตารางสอน
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT,
        subject_id INT NULL,
        activity_type VARCHAR(50) NULL, -- 'guidance', 'scouts', 'club', 'social'
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        day_of_week INT, -- 1=Mon, 2=Tue, ..., 7=Sun
        period_number INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        UNIQUE KEY unique_timetable (classroom_id, academic_year, semester, day_of_week, period_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ activity_type ใน timetables
    $stmt = $pdo->query("SHOW COLUMNS FROM timetables LIKE 'activity_type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE timetables ADD COLUMN activity_type VARCHAR(50) NULL AFTER subject_id");
        $pdo->exec("ALTER TABLE timetables MODIFY COLUMN subject_id INT NULL");
    }
    $results[] = "ตรวจสอบ/สร้างตาราง timetables สำเร็จ";

    // 15. เพิ่มตารางบันทึกการมาเรียน
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT NULL,
        activity_type VARCHAR(50) NULL,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        check_date DATE,
        period_number INT,
        status ENUM('present', 'absent', 'late', 'sick', 'leave') DEFAULT 'present',
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_attendance (student_id, subject_id, activity_type, check_date, period_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ activity_type ใน attendance
    $stmt = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'activity_type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN activity_type VARCHAR(50) NULL AFTER subject_id");
        $pdo->exec("ALTER TABLE attendance MODIFY COLUMN subject_id INT NULL");
        // อัปเดต Unique Key
        $pdo->exec("ALTER TABLE attendance DROP INDEX unique_attendance");
        $pdo->exec("ALTER TABLE attendance ADD UNIQUE KEY unique_attendance (student_id, subject_id, activity_type, check_date, period_number)");
    }
    $results[] = "ตรวจสอบ/สร้างตาราง attendance สำเร็จ";

    echo json_encode([
        'status' => 'success',
        'message' => 'ตรวจสอบและปรับปรุงฐานข้อมูลเรียบร้อยแล้ว',
        'details' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการปรับปรุงฐานข้อมูล: ' . $e->getMessage(),
        'details' => $results
    ]);
}
?>
