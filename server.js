import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;

app.use(express.json());
// Serve static assets except PHP files so Express routes handle them
app.use((req, res, next) => {
    if (req.path.startsWith('/api/') || req.path.endsWith('.php') || req.path === '/') {
        return next();
    }
    express.static(__dirname)(req, res, next);
});

// --- Mock Data Store ---
let mockUsers = [
    { id: 1, username: '0000000000000', name: 'Super Admin System', role: 'super_admin', is_approved: 1, school_id: null, affiliation: 'สพป.บุรีรัมย์ เขต 3', position: 'ผู้ดูแลระบบ', is_academic: 0 },
    { id: 2, username: '1111111111111', name: 'School Admin', role: 'admin', is_approved: 1, school_id: 1, school_name: 'โรงเรียนบ้านหนองบัว', affiliation: 'สพป.บุรีรัมย์ เขต 3', position: 'ผู้อำนวยการ', is_academic: 0 },
    { id: 3, name: 'คุณครูสมชาย ใจดี', position: 'ครูผู้ช่วย', is_approved: 1, role: 'teacher', is_academic: 0, school_id: 1 },
    { id: 4, name: 'คุณครูสมหญิง รักเรียน', position: 'ครู ค.ศ. 1', is_approved: 1, role: 'teacher', is_academic: 1, school_id: 1 },
    { id: 5, name: 'คุณครูมานะ ขยัน', school_name: 'โรงเรียนบ้านหนองบัว', position: 'ครูผู้ช่วย', role: 'teacher', is_approved: 0, school_id: 1 }
];

// --- Mock API (จำลอง PHP เพื่อให้ทดสอบในหน้า Preview ได้) ---
app.post('/api/login.php', (req, res) => {
    const { username, password } = req.body;
    const user = mockUsers.find(u => u.username === username && password === '123456');
    if (user) {
        res.json(user);
    } else {
        res.status(401).json({ error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (ลองใช้ 0000000000000 / 123456)' });
    }
});

app.get('/api/get_schools.php', (req, res) => {
    res.json([
        { id: 1, code: '10310001', name: 'โรงเรียนบ้านหนองบัว', province: 'บุรีรัมย์' },
        { id: 2, code: '10310002', name: 'โรงเรียนบ้านดอนกลาง', province: 'บุรีรัมย์' }
    ]);
});

app.get('/api/get_school_teachers.php', (req, res) => {
    const schoolId = parseInt(req.query.school_id);
    const mockRole = req.query.mock_role || 'admin';
    const currentSchoolId = 1;

    let targetSchoolId = schoolId;
    if (isNaN(targetSchoolId) && mockRole === 'admin') {
        targetSchoolId = currentSchoolId;
    }

    let teachers = mockUsers.filter(u => u.school_id === targetSchoolId);
    
    // เรียงลำดับตามชื่อ
    teachers.sort((a, b) => a.name.localeCompare(b.name, 'th'));
    
    res.json(teachers);
});

app.get('/api/get_pending_users.php', (req, res) => {
    const mockRole = req.query.mock_role || 'admin';
    const currentSchoolId = 1;
    
    let pending = mockUsers.filter(u => u.is_approved === 0);
    
    if (mockRole === 'admin') {
        pending = pending.filter(u => u.school_id === currentSchoolId);
    }
    
    res.json(pending);
});

app.post('/api/approve_user.php', (req, res) => {
    const { user_id, role } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.is_approved = 1;
        if (role) user.role = role;
        res.json({ message: 'อนุมัติผู้ใช้งานสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
});

app.post('/api/admin/promote_to_admin.php', (req, res) => {
    const { user_id } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.role = 'admin';
        res.json({ message: 'กำหนดสิทธิ์เป็น Admin โรงเรียนสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
});

app.post('/api/admin/set_academic_role.php', (req, res) => {
    const { user_id, is_academic } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.is_academic = is_academic ? 1 : 0;
        res.json({ message: 'ปรับปรุงสิทธิ์งานวิชาการสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
});

app.get('/api/academic/get_students.php', (req, res) => {
    res.json([
        { id: 1, student_code: '66001', name: 'เด็กชายกอไก่ ใจดี', level: 'ป.1', room: '1' },
        { id: 2, student_code: '66002', name: 'เด็กหญิงขอไข่ ใฝ่เรียน', level: 'ป.1', room: '1' },
        { id: 3, student_code: '66003', name: 'เด็กชายคอควาย คึกคัก', level: 'ป.1', room: '2' },
        { id: 4, student_code: '66004', name: 'เด็กหญิงงองู เงียบเหงา', level: 'ป.2', room: '1' },
        { id: 5, student_code: '66005', name: 'เด็กชายจจาน จริงใจ', level: 'ป.2', room: '2' }
    ]);
});

app.get('/api/academic/get_subjects.php', (req, res) => {
    res.json([
        { id: 1, code: 'ท11101', name: 'ภาษาไทย', level: 'ป.1', hours: 200, credits: 5.0 },
        { id: 2, code: 'ค11101', name: 'คณิตศาสตร์', level: 'ป.1', hours: 200, credits: 5.0 },
        { id: 3, code: 'ท12101', name: 'ภาษาไทย', level: 'ป.2', hours: 200, credits: 5.0 },
        { id: 4, code: 'ค12101', name: 'คณิตศาสตร์', level: 'ป.2', hours: 200, credits: 5.0 }
    ]);
});

app.post('/api/academic/import_students.php', (req, res) => {
    res.json({ message: 'นำเข้าข้อมูลนักเรียนสำเร็จแล้ว (Mock)' });
});

app.post('/api/academic/import_subjects.php', (req, res) => {
    res.json({ message: 'นำเข้าข้อมูลรายวิชาสำเร็จแล้ว (Mock)' });
});

app.post('/api/reject_user.php', (req, res) => {
    res.json({ message: 'ปฏิเสธการสมัครและลบข้อมูลสำเร็จแล้ว (Mock)' });
});

app.post('/api/admin/save_teacher.php', (req, res) => {
    const { id, name, position, is_academic, username, school_id } = req.body;
    if (id) {
        const idx = mockUsers.findIndex(u => u.id == id);
        if (idx !== -1) {
            mockUsers[idx] = { ...mockUsers[idx], name, position, is_academic: is_academic ? 1 : 0 };
        }
    } else {
        mockUsers.push({
            id: Date.now(),
            username: username || Date.now().toString(),
            name,
            position,
            role: 'teacher',
            is_approved: 1,
            is_academic: is_academic ? 1 : 0,
            school_id: parseInt(school_id) || 1
        });
    }
    res.json({ status: 'success', message: 'บันทึกข้อมูลคุณครูเรียบร้อยแล้ว (Mock)' });
});

let mockAssignments = [
    { assignment_id: 1, teacher_id: 3, teacher_name: 'คุณครูสมชาย ใจดี', code: 'ท11101', name: 'ภาษาไทย', level: 'ป.1', room: '1', hours: 200, credits: 5.0, academic_year: '2567', semester: 1 },
    { assignment_id: 2, teacher_id: 4, teacher_name: 'คุณครูสมหญิง รักเรียน', code: 'ค11101', name: 'คณิตศาสตร์', level: 'ป.1', room: '1', hours: 200, credits: 5.0, academic_year: '2567', semester: 1 },
    { assignment_id: 3, teacher_id: 3, teacher_name: 'คุณครูสมชาย ใจดี', code: 'ว11101', name: 'วิทยาศาสตร์', level: 'ป.1', room: '1', hours: 80, credits: 2.0, academic_year: '2567', semester: 1 }
];

app.post('/api/admin/delete_teacher.php', (req, res) => {
    const { id, transfer_to_id } = req.body;
    const hasAssignments = mockAssignments.some(a => a.teacher_id == id);
    if (hasAssignments && !transfer_to_id) {
        return res.json({
            error: 'คุณครูท่านนี้ยังมีภาระงานสอนที่รับผิดชอบ หากคุณครูย้ายโรงเรียน แนะนำให้โอนย้ายงานสอนไปยังคุณครูท่านอื่นก่อนลบ เพื่อป้องกันข้อมูลคะแนนสูญหาย',
            has_active_data: true,
            ta_count: mockAssignments.filter(a => a.teacher_id == id).length
        });
    }
    if (transfer_to_id) {
        mockAssignments.forEach(a => {
            if (a.teacher_id == id) {
                const targetTeacher = mockUsers.find(u => u.id == transfer_to_id);
                a.teacher_id = parseInt(transfer_to_id);
                if (targetTeacher) a.teacher_name = targetTeacher.name;
            }
        });
    }
    mockUsers = mockUsers.filter(u => u.id != id);
    res.json({ status: 'success', message: 'ลบข้อมูลคุณครูเรียบร้อยแล้ว (Mock)' });
});

app.get('/api/admin/get_teacher_assignments.php', (req, res) => {
    const teacherId = parseInt(req.query.teacher_id);
    const list = isNaN(teacherId) ? mockAssignments : mockAssignments.filter(a => a.teacher_id === teacherId);
    res.json(list);
});

app.get('/api/teacher/get_my_assignments.php', (req, res) => {
    const teacherId = parseInt(req.query.teacher_id) || 3;
    const semester = req.query.semester;
    
    let list = mockAssignments.filter(a => a.teacher_id === teacherId);
    if (semester && semester !== 'annual') {
        list = list.filter(a => a.semester == semester || !a.semester);
    }
    res.json(list.map(a => ({
        assignment_id: a.assignment_id,
        subject_id: a.assignment_id,
        subject_code: a.code,
        code: a.code,
        subject_name: a.name,
        level: a.level,
        classroom_id: 1,
        room: a.room || '1',
        teacher_name: a.teacher_name
    })));
});

app.get('/api/admin/get_all_assignments.php', (req, res) => {
    const semester = req.query.semester;
    
    let list = [...mockAssignments];
    if (semester && semester !== 'annual') {
        list = list.filter(a => a.semester == semester || !a.semester);
    }
    res.json(list.map(a => ({
        assignment_id: a.assignment_id,
        subject_id: a.assignment_id,
        subject_code: a.code,
        code: a.code,
        subject_name: a.name,
        level: a.level,
        classroom_id: 1,
        room: a.room || '1',
        teacher_name: a.teacher_name
    })));
});

app.post('/api/admin/change_assignment_teacher.php', (req, res) => {
    const { assignment_id, new_teacher_id } = req.body;
    const assignment = mockAssignments.find(a => a.assignment_id == assignment_id);
    const newTeacher = mockUsers.find(u => u.id == new_teacher_id);
    if (!assignment) return res.status(404).json({ error: 'ไม่พบงานสอน' });
    if (!newTeacher) return res.status(404).json({ error: 'ไม่พบข้อมูลคุณครูท่านใหม่' });

    assignment.teacher_id = parseInt(new_teacher_id);
    assignment.teacher_name = newTeacher.name;

    res.json({
        status: 'success',
        message: `เปลี่ยนครูผู้สอนเป็น คุณครู${newTeacher.name} เรียบร้อยแล้ว (คะแนนและข้อมูลที่เคยบันทึกไว้ทั้งหมดถูกโอนไปยังครูท่านใหม่เรียบร้อย)`,
        new_teacher_name: newTeacher.name
    });
});

app.post('/api/admin/transfer_teacher_assignments.php', (req, res) => {
    const { from_teacher_id, to_teacher_id } = req.body;
    const fromTeacher = mockUsers.find(u => u.id == from_teacher_id);
    const toTeacher = mockUsers.find(u => u.id == to_teacher_id);
    if (!fromTeacher || !toTeacher) return res.status(404).json({ error: 'ไม่พบคุณครูในระบบ' });

    let count = 0;
    mockAssignments.forEach(a => {
        if (a.teacher_id == from_teacher_id) {
            a.teacher_id = parseInt(to_teacher_id);
            a.teacher_name = toTeacher.name;
            count++;
        }
    });

    res.json({
        status: 'success',
        message: `โอนย้ายภาระงานสอนและคะแนนจาก คุณครู${fromTeacher.name} ไปยัง คุณครู${toTeacher.name} สำเร็จเรียบร้อยแล้ว`,
        transferred_courses: count,
        transferred_grades: count * 30
    });
});

app.get('/api/admin/get_grading_progress.php', (req, res) => {
    const list = mockAssignments.map(a => ({
        assignment_id: a.assignment_id,
        teacher_id: a.teacher_id,
        subject_name: a.name,
        subject_code: a.code,
        subject_level: a.level,
        room: a.room || '1',
        classroom_id: 1,
        teacher_name: a.teacher_name,
        teacher_last_name: '',
        student_count: 30,
        total_units: 4,
        completed_units: 4,
        final_count: 30,
        characteristics_count: 30,
        analytical_count: 30,
        competency_count: 30,
        learner_dev_count: 30
    }));
    res.json(list);
});

app.get('/api/admin/get_assignment_score_details.php', (req, res) => {
    const assignmentId = parseInt(req.query.assignment_id);
    const a = mockAssignments.find(x => x.assignment_id === assignmentId) || mockAssignments[0];
    res.json({
        assignment: {
            id: a.assignment_id,
            teacher_id: a.teacher_id,
            subject_name: a.name,
            subject_code: a.code,
            level: a.level,
            room: a.room || '1',
            teacher_name: a.teacher_name,
            teacher_last_name: '',
            academic_year: '2567',
            semester: 1
        },
        units: [
            { id: 1, unit_name: 'หน่วยที่ 1 การอ่าน', max_score: 10 },
            { id: 2, unit_name: 'หน่วยที่ 2 การเขียน', max_score: 10 }
        ],
        students: [
            { id: 1, student_code: '66001', full_name: 'เด็กชายกอไก่ ใจดี', unit_scores: { 1: 9, 2: 8 }, final_score: 28, total_score: 85, grade: '4' },
            { id: 2, student_code: '66002', full_name: 'เด็กหญิงขอไข่ ใฝ่เรียน', unit_scores: { 1: 8, 2: 7 }, final_score: 25, total_score: 78, grade: '3.5' }
        ]
    });
});

app.post('/api/admin/assign_subjects.php', (req, res) => {
    res.json({ message: 'มอบหมายงานสอนสำเร็จแล้ว (Mock)' });
});

app.post('/api/admin/remove_assignment.php', (req, res) => {
    res.json({ message: 'ยกเลิกงานสอนสำเร็จแล้ว (Mock)' });
});

app.post('/api/register.php', (req, res) => {
    res.json({ message: 'จำลองการสมัครสมาชิกสำเร็จ! (ในระบบจริงจะตรวจสอบรหัสโรงเรียน 8 หลัก)' });
});

// --- School Settings & Logo Upload Mocks ---
let mockSchool = {
    id: 1,
    name: 'โรงเรียนบ้านหนองบัว',
    province: 'บุรีรัมย์',
    logo_url: '',
    director_name: 'นายสยาม เชียงเครือ',
    academic_head_name: 'นางสาวสมศรี รักเรียน',
    academic_head_position: 'หัวหน้างานวิชาการ',
    primary_grading_mode: 'average'
};

app.get('/api/academic/get_classrooms.php', (req, res) => {
    res.json([
        { id: 1, level: 'ป.1', room: '1', teacher_id_1: 2, teacher_name_1: 'School Admin' },
        { id: 2, level: 'ป.2', room: '1', teacher_id_1: null, teacher_name_1: null }
    ]);
});

app.post('/api/academic/update_classroom_teachers.php', (req, res) => {
    res.json({ status: 'success', message: 'อัปเดตครูประจำชั้นเรียบร้อยแล้ว (Mock)' });
});

// --- Mock Attendance Endpoints for Preview ---
const mockAttendanceClassrooms = [
    {
        id: 1, level: 'ป.1', room: '1', school_id: 1,
        subjects: [
            { subject_id: 101, subject_code: 'ค11101', subject_name: 'คณิตศาสตร์ 1', level: 'ป.1' },
            { subject_id: 102, subject_code: 'ท11101', subject_name: 'ภาษาไทย 1', level: 'ป.1' }
        ]
    },
    {
        id: 2, level: 'ป.2', room: '1', school_id: 1,
        subjects: [
            { subject_id: 201, subject_code: 'ค12101', subject_name: 'คณิตศาสตร์ 2', level: 'ป.2' },
            { subject_id: 202, subject_code: 'ว12101', subject_name: 'วิทยาศาสตร์ 2', level: 'ป.2' }
        ]
    },
    {
        id: 3, level: 'ป.3', room: '1', school_id: 1,
        subjects: [
            { subject_id: 301, subject_code: 'ค13101', subject_name: 'คณิตศาสตร์ 3', level: 'ป.3' },
            { subject_id: 302, subject_code: 'อ13101', subject_name: 'ภาษาอังกฤษ 3', level: 'ป.3' }
        ]
    },
    {
        id: 4, level: 'ป.4', room: '1', school_id: 1,
        subjects: [
            { subject_id: 401, subject_code: 'ค14101', subject_name: 'คณิตศาสตร์ 4', level: 'ป.4' }
        ]
    },
    {
        id: 5, level: 'ป.5', room: '1', school_id: 1,
        subjects: [
            { subject_id: 501, subject_code: 'ค15101', subject_name: 'คณิตศาสตร์ 5', level: 'ป.5' }
        ]
    },
    {
        id: 6, level: 'ป.6', room: '1', school_id: 1,
        subjects: [
            { subject_id: 601, subject_code: 'ค16101', subject_name: 'คณิตศาสตร์ 6', level: 'ป.6' }
        ]
    }
];

const mockStudentsByClassroom = {
    1: [
        { id: 101, student_code: '66001', prefix: 'เด็กชาย', name: 'ก้องภพ', last_name: 'ใจดี' },
        { id: 102, student_code: '66002', prefix: 'เด็กหญิง', name: 'ขวัญข้าว', last_name: 'สุขสันต์' },
        { id: 103, student_code: '66003', prefix: 'เด็กชาย', name: 'จิรายุ', last_name: 'วัฒนา' },
        { id: 104, student_code: '66004', prefix: 'เด็กหญิง', name: 'ชลธิชา', last_name: 'บุญมี' },
        { id: 105, student_code: '66005', prefix: 'เด็กชาย', name: 'ณัฐพงษ์', last_name: 'เจริญผล' }
    ],
    2: [
        { id: 201, student_code: '65001', prefix: 'เด็กชาย', name: 'ปิติ', last_name: 'รักชาติ' },
        { id: 202, student_code: '65002', prefix: 'เด็กหญิง', name: 'มานี', last_name: 'มีตา' },
        { id: 203, student_code: '65003', prefix: 'เด็กชาย', name: 'ชูใจ', last_name: 'เลิศล้ำ' }
    ]
};

app.get('/api/teacher/get_attendance_classrooms.php', (req, res) => {
    res.json(mockAttendanceClassrooms);
});

app.get('/api/teacher/get_daily_schedule.php', (req, res) => {
    const checkDate = req.query.check_date || new Date().toISOString().split('T')[0];
    const dateObj = new Date(checkDate);
    const dayOfWeek = dateObj.getDay() === 0 ? 7 : dateObj.getDay(); // 1=Mon..7=Sun
    const thaiDays = ['', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์', 'วันอาทิตย์'];
    const thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    const formattedDate = `${thaiDays[dayOfWeek]}ที่ ${dateObj.getDate()} ${thaiMonths[dateObj.getMonth() + 1]} พ.ศ. ${dateObj.getFullYear() + 543}`;

    // Sample daily timetable schedule
    const schedule = [
        { id: 1, period_number: 1, classroom_id: 1, classroom_name: 'ป.1/1', level: 'ป.1', room: '1', subject_id: 101, subject_code: 'ค11101', subject_name: 'คณิตศาสตร์ 1' },
        { id: 2, period_number: 2, classroom_id: 2, classroom_name: 'ป.2/1', level: 'ป.2', room: '1', subject_id: 201, subject_code: 'ค12101', subject_name: 'คณิตศาสตร์ 2' },
        { id: 3, period_number: 3, classroom_id: 3, classroom_name: 'ป.3/1', level: 'ป.3', room: '1', subject_id: 301, subject_code: 'ค13101', subject_name: 'คณิตศาสตร์ 3' },
        { id: 4, period_number: 4, classroom_id: 4, classroom_name: 'ป.4/1', level: 'ป.4', room: '1', subject_id: 401, subject_code: 'ค14101', subject_name: 'คณิตศาสตร์ 4' }
    ];

    res.json({
        check_date: checkDate,
        day_of_week: dayOfWeek,
        day_name: thaiDays[dayOfWeek],
        formatted_thai_date: formattedDate,
        schedule: schedule,
        total_classes: schedule.length,
        is_weekend: dayOfWeek >= 6
    });
});

app.get('/api/teacher/get_attendance_data.php', (req, res) => {
    const classId = parseInt(req.query.classroom_id) || 1;
    const cls = mockAttendanceClassrooms.find(c => c.id === classId) || mockAttendanceClassrooms[0];
    const students = mockStudentsByClassroom[classId] || mockStudentsByClassroom[1];
    
    res.json({
        classroom: cls,
        subjects: cls.subjects.map((s, idx) => ({ ...s, period_number: idx + 1 })),
        students: students,
        attendance: []
    });
});

app.post('/api/teacher/save_attendance.php', (req, res) => {
    res.json({ status: 'success', message: 'บันทึกการมาเรียนเรียบร้อยแล้ว (Mock)' });
});

app.get('/api/teacher/get_monthly_attendance_data.php', (req, res) => {
    const classId = parseInt(req.query.classroom_id) || 1;
    const subId = req.query.subject_id;
    const month = req.query.month || '2026-07';
    const cls = mockAttendanceClassrooms.find(c => c.id === classId) || mockAttendanceClassrooms[0];
    const sub = cls.subjects.find(s => s.subject_id == subId) || cls.subjects[0];
    const students = mockStudentsByClassroom[classId] || mockStudentsByClassroom[1];

    // Mock 8 sessions in the month
    const parts = month.split('-');
    const y = parts[0];
    const m = parts[1];
    const sessions = [
        { date: `${y}-${m}-02`, day: 2, dow: 4, day_name: 'พฤหัสบดี', period_number: 1 },
        { date: `${y}-${m}-06`, day: 6, dow: 1, day_name: 'จันทร์', period_number: 1 },
        { date: `${y}-${m}-09`, day: 9, dow: 4, day_name: 'พฤหัสบดี', period_number: 1 },
        { date: `${y}-${m}-13`, day: 13, dow: 1, day_name: 'จันทร์', period_number: 1 },
        { date: `${y}-${m}-16`, day: 16, dow: 4, day_name: 'พฤหัสบดี', period_number: 1 },
        { date: `${y}-${m}-20`, day: 20, dow: 1, day_name: 'จันทร์', period_number: 1 },
        { date: `${y}-${m}-23`, day: 23, dow: 4, day_name: 'พฤหัสบดี', period_number: 1 },
        { date: `${y}-${m}-27`, day: 27, dow: 1, day_name: 'จันทร์', period_number: 1 }
    ];

    const student_summary = {};
    students.forEach(s => {
        student_summary[s.id] = { present: 8, absent: 0, late: 0, leave: 0, sick: 0, total_recorded: 8 };
    });

    res.json({
        classroom: cls,
        subject: sub,
        month: month,
        total_sessions: sessions.length,
        teaching_sessions: sessions,
        has_timetable: true,
        students: students,
        existing_attendance: [],
        student_summary: student_summary
    });
});

app.post('/api/teacher/save_monthly_attendance.php', (req, res) => {
    const { students = [], sessions = [] } = req.body;
    res.json({
        status: 'success',
        message: `บันทึกเวลาเรียนทั้งเดือนเรียบร้อยแล้ว (${students.length} คน x ${sessions.length} คาบ รวม ${students.length * sessions.length} รายการ)`,
        total_records: students.length * sessions.length,
        student_count: students.length,
        session_count: sessions.length
    });
});

let mockOfficials = [
    { id: 1, school_id: 1, name: 'นายสยาม เชียงเครือ', position: 'ผู้อำนวยการโรงเรียนบ้านหนองบัว', role_key: 'director' },
    { id: 2, school_id: 1, name: 'นางสาวสมศรี รักเรียน', position: 'หัวหน้างานวิชาการ', role_key: 'academic_head' }
];

app.get('/api/admin/get_school_officials.php', (req, res) => {
    res.json(mockOfficials);
});

app.post('/api/admin/save_school_official.php', (req, res) => {
    const { id, name, position, role_key } = req.body;
    if (id) {
        const idx = mockOfficials.findIndex(o => o.id == id);
        if (idx !== -1) {
            mockOfficials[idx] = { ...mockOfficials[idx], name, position, role_key };
        }
    } else {
        mockOfficials.push({ id: Date.now(), school_id: 1, name, position, role_key });
    }
    res.json({ status: 'success', message: 'บันทึกข้อมูลสำเร็จ (Mock)' });
});

app.post('/api/admin/delete_school_official.php', (req, res) => {
    const { id } = req.body;
    mockOfficials = mockOfficials.filter(o => o.id != id);
    res.json({ status: 'success', message: 'ลบข้อมูลสำเร็จ (Mock)' });
});

app.get('/api/admin/get_school_info.php', (req, res) => {
    res.json({
        status: 'success',
        school: mockSchool
    });
});

app.post('/api/admin/update_school_settings.php', (req, res) => {
    const { name, province, logo_url, director_name, academic_head_name, academic_head_position, primary_grading_mode } = req.body;
    mockSchool.name = name;
    mockSchool.province = province;
    mockSchool.logo_url = logo_url;
    mockSchool.director_name = director_name;
    mockSchool.academic_head_name = academic_head_name;
    mockSchool.academic_head_position = academic_head_position;
    mockSchool.primary_grading_mode = primary_grading_mode || 'average';
    res.json({ status: 'success', message: 'อัปเดตข้อมูลโรงเรียนเรียบร้อยแล้ว (Mock)' });
});

app.post('/api/admin/upload_logo.php', (req, res) => {
    // ใน Preview เราจะจำลองการอัปโหลดโดยใช้รูปภาพ Placeholder
    // หรือถ้ามีการส่งไฟล์มาจริงๆ เราจะตอบกลับด้วย URL จำลอง
    res.json({
        status: 'success',
        url: 'https://picsum.photos/seed/school/200/200'
    });
});

// --- Static File Serving with Mock PHP Replacement ---
const servePhpAsHtml = (filePath, req, res) => {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        
        // จำลองการแทนที่ตัวแปร PHP พื้นฐานสำหรับหน้า Preview
        // ในระบบจริง PHP จะจัดการส่วนนี้เอง
        const mockRole = req.query.mock_role || 'admin';
        const mockSession = {
            user_id: mockRole === 'super_admin' ? 1 : 2,
            name: mockRole === 'super_admin' ? 'Super Admin System' : 'School Admin',
            role: mockRole,
            school_id: mockRole === 'super_admin' ? null : 1,
            school_name: mockRole === 'super_admin' ? null : 'โรงเรียนบ้านหนองบัว',
            affiliation: 'สพป.บุรีรัมย์ เขต 3',
            is_academic: 0
        };

        // ประมวลผล include / require
        const processIncludes = (text, currentDir) => {
            return text.replace(/<\?php\s*(?:include|require|require_once|include_once)\s*['"](.*?)['"]\s*;\s*\?>/g, (match, relPath) => {
                const fullIncludePath = path.resolve(currentDir, relPath);
                if (fs.existsSync(fullIncludePath)) {
                    return processIncludes(fs.readFileSync(fullIncludePath, 'utf8'), path.dirname(fullIncludePath));
                }
                return '';
            });
        };
        content = processIncludes(content, path.dirname(filePath));

        // แทนที่ <?= ... ?>
        content = content.replace(/<\?=\s*\$_SESSION\['(.*?)'\]\s*\?>/g, (match, key) => {
            return mockSession[key] !== undefined ? mockSession[key] : '';
        });
        content = content.replace(/<\?=\s*\$username\s*\?>/g, mockSession.name);
        content = content.replace(/<\?=\s*\$role\s*\?>/g, mockSession.role);
        content = content.replace(/<\?=\s*\$school_name\s*\?>/g, mockSession.school_name);
        content = content.replace(/<\?=\s*\$affiliation\s*\?>/g, mockSession.affiliation);
        content = content.replace(/<\?=\s*mb_substr\(\$username,\s*0,\s*1\)\s*\?>/g, mockSession.name.charAt(0));

        // แทนที่เงื่อนไข <?php if ($role === '...'): ?> ... <?php endif; ?>
        // แบบที่รองรับการซ้อนกัน (Nesting)
        const evaluateCondition = (cond, session) => {
            const role = session.role;
            const is_academic = session.is_academic;
            
            // ทำความสะอาดเงื่อนไข
            let c = cond.trim();
            
            // จัดการ ||
            if (c.includes('||')) {
                return c.split('||').some(part => evaluateCondition(part, session));
            }
            
            // จัดการ &&
            if (c.includes('&&')) {
                return c.split('&&').every(part => evaluateCondition(part, session));
            }

            if (c.includes("$role === 'super_admin'")) return role === 'super_admin';
            if (c.includes("$role === 'admin'")) return role === 'admin';
            if (c.includes("$role === 'teacher'")) return role === 'teacher';
            if (c.includes("$_SESSION['is_academic']")) return is_academic === 1;
            if (c.includes("isset($_SESSION['is_academic'])")) return true;
            
            return false;
        };

        const processIfBlocks = (text) => {
            let oldText;
            let iter = 0;
            do {
                oldText = text;
                // ค้นหาบล็อก if/endif ที่อยู่ชั้นในสุด (Innermost) และรองรับ else block
                text = text.replace(/<\?php\s*if\s*\((.*?)\):\s*\?>(?!.*?<\?php\s*if\s*\(.*?\):\s*\?>)([\s\S]*?)<\?php\s*endif;\s*\?>/g, (match, condition, inner) => {
                    const result = evaluateCondition(condition, mockSession);
                    if (inner.includes('<?php else: ?>')) {
                        const parts = inner.split('<?php else: ?>');
                        return result ? parts[0] : (parts[1] || '');
                    } else if (inner.includes('<?php else : ?>')) {
                        const parts = inner.split('<?php else : ?>');
                        return result ? parts[0] : (parts[1] || '');
                    }
                    return result ? inner : '';
                });
                iter++;
            } while (text !== oldText && iter < 100);
            return text;
        };

        content = processIfBlocks(content);

        // ลบแท็ก PHP อื่นๆ ที่เหลือ
        content = content.replace(/<\?php.*?\?>/gs, '');

        res.setHeader('Content-Type', 'text/html');
        res.send(content);
    } else {
        res.status(404).send('File not found.');
    }
};

app.get('/', (req, res) => {
    servePhpAsHtml(path.join(__dirname, 'index.php'), req, res);
});

app.get('/dashboard.php', (req, res) => {
    servePhpAsHtml(path.join(__dirname, 'dashboard.php'), req, res);
});

app.get('*.php', (req, res) => {
    servePhpAsHtml(path.join(__dirname, req.path), req, res);
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Preview server running on http://localhost:${PORT}`);
});
