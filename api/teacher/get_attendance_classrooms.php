<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'teacher';
$is_admin = ($user_role === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'] == 1));

// ใช้ปีการศึกษาที่ส่งมา หรือปีปัจจุบัน
$academic_year = $_GET['academic_year'] ?? '';
if (empty($academic_year)) {
    try {
        $stmt_y = $pdo->prepare("SELECT year FROM academic_years WHERE school_id = ? AND is_current = 1 LIMIT 1");
        $stmt_y->execute([$school_id]);
        $y_row = $stmt_y->fetch();
        $academic_year = $y_row ? $y_row['year'] : '2567';
    } catch (Exception $e) {
        $academic_year = '2567';
    }
}
$semester = $_GET['semester'] ?? 1;

try {
    $classrooms_map = [];

    if ($is_admin) {
        // ผู้ดูแลระบบ/วิชาการ เห็นทุกห้องเรียนของโรงเรียน
        $stmt = $pdo->prepare("
            SELECT id, level, room, school_id, teacher_id_1, teacher_id_2 
            FROM classrooms 
            WHERE school_id = ? OR ? = 0
            ORDER BY level ASC, room ASC
        ");
        $stmt->execute([$school_id, $school_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $classrooms_map[$r['id']] = $r;
        }
    } else {
        // 1. ดึงห้องเรียนจากภาระงานสอนรายวิชา (teacher_assignments) ที่ระบุห้องเรียน
        $stmt_ta = $pdo->prepare("
            SELECT DISTINCT c.id, c.level, c.room, c.school_id, c.teacher_id_1, c.teacher_id_2
            FROM teacher_assignments ta
            JOIN classrooms c ON ta.classroom_id = c.id
            WHERE ta.teacher_id = ? 
              AND (ta.academic_year = ? OR ta.academic_year IS NULL OR ta.academic_year = '')
              AND (ta.semester = ? OR ta.semester = 0 OR ta.semester IS NULL OR ta.semester = '' OR ? = 'annual')
              AND (c.school_id = ? OR c.school_id IS NULL OR c.school_id = 0 OR ? = 0)
        ");
        $stmt_ta->execute([$teacher_id, $academic_year, $semester, $semester, $school_id, $school_id]);
        while ($r = $stmt_ta->fetch(PDO::FETCH_ASSOC)) {
            $classrooms_map[$r['id']] = $r;
        }

        // กรณี teacher_assignments ไม่มี classroom_id ให้จับคู่กับระดับชั้นของรายวิชา (เช่น สอน ป.1 ให้เห็น ป.1 ทุกห้อง)
        $stmt_ta_lvl = $pdo->prepare("
            SELECT DISTINCT c.id, c.level, c.room, c.school_id, c.teacher_id_1, c.teacher_id_2
            FROM teacher_assignments ta
            JOIN subjects s ON ta.subject_id = s.id
            JOIN classrooms c ON (
                c.level = s.level 
                OR c.level = REPLACE(s.level, 'ประถมศึกษาปีที่ ', 'ป.')
                OR s.level = REPLACE(c.level, 'ประถมศึกษาปีที่ ', 'ป.')
                OR c.level = REPLACE(s.level, 'มัธยมศึกษาปีที่ ', 'ม.')
                OR s.level = REPLACE(c.level, 'มัธยมศึกษาปีที่ ', 'ม.')
                OR c.level = REPLACE(s.level, 'ชั้นประถมศึกษาปีที่ ', 'ป.')
                OR s.level = REPLACE(c.level, 'ชั้นประถมศึกษาปีที่ ', 'ป.')
                OR c.level = REPLACE(s.level, 'ชั้นมัธยมศึกษาปีที่ ', 'ม.')
                OR s.level = REPLACE(c.level, 'ชั้นมัธยมศึกษาปีที่ ', 'ม.')
                OR REPLACE(c.level, ' ', '') = REPLACE(s.level, ' ', '')
            )
            WHERE ta.teacher_id = ? 
              AND (ta.academic_year = ? OR ta.academic_year IS NULL OR ta.academic_year = '')
              AND (ta.semester = ? OR ta.semester = 0 OR ta.semester IS NULL OR ta.semester = '' OR ? = 'annual')
              AND (ta.classroom_id IS NULL OR ta.classroom_id = 0)
              AND (c.school_id = ? OR c.school_id IS NULL OR c.school_id = 0 OR ? = 0)
        ");
        $stmt_ta_lvl->execute([$teacher_id, $academic_year, $semester, $semester, $school_id, $school_id]);
        while ($r = $stmt_ta_lvl->fetch(PDO::FETCH_ASSOC)) {
            $classrooms_map[$r['id']] = $r;
        }

        // 2. ดึงห้องเรียนจากตารางสอน (timetables)
        $stmt_tt = $pdo->prepare("
            SELECT DISTINCT c.id, c.level, c.room, c.school_id, c.teacher_id_1, c.teacher_id_2
            FROM timetables t
            JOIN classrooms c ON t.classroom_id = c.id
            WHERE t.teacher_id = ? 
              AND (t.academic_year = ? OR t.academic_year IS NULL OR t.academic_year = '')
              AND (t.semester = ? OR t.semester = 0 OR t.semester IS NULL OR ? = 'annual')
              AND (c.school_id = ? OR c.school_id IS NULL OR c.school_id = 0 OR ? = 0)
        ");
        $stmt_tt->execute([$teacher_id, $academic_year, $semester, $semester, $school_id, $school_id]);
        while ($r = $stmt_tt->fetch(PDO::FETCH_ASSOC)) {
            $classrooms_map[$r['id']] = $r;
        }

        // 3. ดึงห้องเรียนที่เป็นครูประจำชั้น (classrooms)
        $stmt_hr = $pdo->prepare("
            SELECT id, level, room, school_id, teacher_id_1, teacher_id_2
            FROM classrooms
            WHERE (teacher_id_1 = ? OR teacher_id_2 = ?) 
              AND (school_id = ? OR school_id IS NULL OR school_id = 0 OR ? = 0)
        ");
        $stmt_hr->execute([$teacher_id, $teacher_id, $school_id, $school_id]);
        while ($r = $stmt_hr->fetch(PDO::FETCH_ASSOC)) {
            $classrooms_map[$r['id']] = $r;
        }

        // 4. ดึงห้องเรียนจากกิจกรรมพัฒนาผู้เรียน (learner_development_assignments)
        $stmt_ld = $pdo->prepare("
            SELECT DISTINCT c.id, c.level, c.room, c.school_id, c.teacher_id_1, c.teacher_id_2
            FROM learner_development_assignments lda
            JOIN classrooms c ON lda.classroom_id = c.id
            WHERE lda.teacher_id = ? 
              AND (lda.academic_year = ? OR lda.academic_year IS NULL OR lda.academic_year = '')
              AND (c.school_id = ? OR c.school_id IS NULL OR c.school_id = 0 OR ? = 0)
        ");
        $stmt_ld->execute([$teacher_id, $academic_year, $school_id, $school_id]);
        while ($r = $stmt_ld->fetch(PDO::FETCH_ASSOC)) {
            $classrooms_map[$r['id']] = $r;
        }

        // 5. หากยังไม่พบห้องเรียนเลย ให้ดึงห้องเรียนจากทุกปีการศึกษาที่ครูได้รับมอบหมาย
        if (empty($classrooms_map)) {
            $stmt_fallback = $pdo->prepare("
                SELECT DISTINCT c.id, c.level, c.room, c.school_id, c.teacher_id_1, c.teacher_id_2
                FROM teacher_assignments ta
                JOIN classrooms c ON ta.classroom_id = c.id
                WHERE ta.teacher_id = ?
            ");
            $stmt_fallback->execute([$teacher_id]);
            while ($r = $stmt_fallback->fetch(PDO::FETCH_ASSOC)) {
                $classrooms_map[$r['id']] = $r;
            }
        }

        // 6. หากยังไม่พบอีก ให้ดึงห้องเรียนทั้งหมดของโรงเรียน เพื่อไม่ให้ครูถูกบล็อกการบันทึกเวลาเรียน
        if (empty($classrooms_map)) {
            $stmt_all = $pdo->prepare("
                SELECT id, level, room, school_id, teacher_id_1, teacher_id_2 
                FROM classrooms 
                WHERE school_id = ? OR ? = 0
                ORDER BY level ASC, room ASC
            ");
            $stmt_all->execute([$school_id, $school_id]);
            while ($r = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
                $classrooms_map[$r['id']] = $r;
            }
        }
    }

    $classrooms = array_values($classrooms_map);

    // เรียงลำดับระดับชั้นและห้องเรียน
    usort($classrooms, function($a, $b) {
        $cmp = strcmp($a['level'], $b['level']);
        if ($cmp !== 0) return $cmp;
        return strcmp($a['room'], $b['room']);
    });

    // ดึงรายวิชาที่ครูท่านนี้สอนในแต่ละห้องเรียน
    foreach ($classrooms as &$c) {
        $cid = $c['id'];
        $c_subjects = [];

        if ($is_admin) {
            $stmt_s = $pdo->prepare("
                SELECT DISTINCT s.id as subject_id, s.code as subject_code, s.name as subject_name, s.level
                FROM subjects s
                LEFT JOIN teacher_assignments ta ON s.id = ta.subject_id AND ta.classroom_id = ? AND (ta.academic_year = ? OR ta.academic_year IS NULL)
                WHERE (s.school_id = ? OR ? = 0) 
                  AND (ta.id IS NOT NULL OR s.level = ? OR s.level = REPLACE(?, 'ประถมศึกษาปีที่ ', 'ป.') OR s.level = REPLACE(?, 'มัธยมศึกษาปีที่ ', 'ม.'))
                ORDER BY s.code ASC
            ");
            $stmt_s->execute([$cid, $academic_year, $school_id, $school_id, $c['level'], $c['level'], $c['level']]);
            $c_subjects = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // ดึงวิชาที่ครูสอนในห้องนี้จาก teacher_assignments
            $stmt_s = $pdo->prepare("
                SELECT DISTINCT s.id as subject_id, s.code as subject_code, s.name as subject_name, s.level
                FROM teacher_assignments ta
                JOIN subjects s ON ta.subject_id = s.id
                WHERE ta.teacher_id = ? 
                  AND (ta.academic_year = ? OR ta.academic_year IS NULL OR ta.academic_year = '')
                  AND (ta.semester = ? OR ta.semester = 0 OR ta.semester IS NULL OR ta.semester = '' OR ? = 'annual')
                  AND (
                      ta.classroom_id = ? 
                      OR (
                          (ta.classroom_id IS NULL OR ta.classroom_id = 0) 
                          AND (
                              s.level = ? 
                              OR s.level = REPLACE(?, 'ประถมศึกษาปีที่ ', 'ป.') 
                              OR ? = REPLACE(s.level, 'ประถมศึกษาปีที่ ', 'ป.')
                              OR s.level = REPLACE(?, 'มัธยมศึกษาปีที่ ', 'ม.')
                              OR ? = REPLACE(s.level, 'มัธยมศึกษาปีที่ ', 'ม.')
                          )
                      )
                  )
                ORDER BY s.code ASC
            ");
            $stmt_s->execute([$teacher_id, $academic_year, $semester, $semester, $cid, $c['level'], $c['level'], $c['level'], $c['level'], $c['level']]);
            $c_subjects = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

            // เพิ่มวิชาจากตารางสอนถ้ามี
            $stmt_tt_s = $pdo->prepare("
                SELECT DISTINCT s.id as subject_id, s.code as subject_code, s.name as subject_name, s.level
                FROM timetables t
                JOIN subjects s ON t.subject_id = s.id
                WHERE t.teacher_id = ? AND t.classroom_id = ? 
                  AND (t.academic_year = ? OR t.academic_year IS NULL OR t.academic_year = '')
                  AND (t.semester = ? OR t.semester = 0 OR ? = 'annual')
                ORDER BY s.code ASC
            ");
            $stmt_tt_s->execute([$teacher_id, $cid, $academic_year, $semester, $semester]);
            $tt_subjects = $stmt_tt_s->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tt_subjects as $tts) {
                if (!array_filter($c_subjects, function($x) use ($tts) { return $x['subject_id'] == $tts['subject_id']; })) {
                    $c_subjects[] = $tts;
                }
            }

            // ถ้าห้องนี้ยังไม่มีรายวิชาที่ผูกไว้ ให้ดึงรายวิชาที่ตรงกับระดับชั้น
            if (empty($c_subjects)) {
                $stmt_lvl_s = $pdo->prepare("
                    SELECT DISTINCT s.id as subject_id, s.code as subject_code, s.name as subject_name, s.level
                    FROM subjects s
                    WHERE (s.school_id = ? OR ? = 0)
                      AND (
                          s.level = ? 
                          OR s.level = REPLACE(?, 'ประถมศึกษาปีที่ ', 'ป.') 
                          OR ? = REPLACE(s.level, 'ประถมศึกษาปีที่ ', 'ป.')
                      )
                    ORDER BY s.code ASC
                ");
                $stmt_lvl_s->execute([$school_id, $school_id, $c['level'], $c['level'], $c['level']]);
                $c_subjects = $stmt_lvl_s->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $c['subjects'] = $c_subjects;
    }

    echo json_encode($classrooms);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
