<!-- Attendance Recording Section -->
<div id="record-attendance" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <!-- Top Header & Mode Switcher -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6 pb-6 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">บันทึกการมาเรียนของนักเรียน</h3>
                        <p class="text-sm text-slate-500">เช็คชื่อรายวันตามตารางสอน หรือบันทึกรายเดือนอ้างอิงจากตารางสอนในคลิกเดียว</p>
                    </div>
                </div>
            </div>

            <!-- Global Academic Controls & Mode Selection -->
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <!-- Mode Toggle Tabs -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-bold">
                    <button type="button" onclick="switchAttendanceMode('daily')" id="btn-mode-daily"
                        class="px-4 py-2 rounded-lg transition-all cursor-pointer bg-white text-blue-600 shadow-sm">
                        📅 บันทึกรายวัน (Daily)
                    </button>
                    <button type="button" onclick="switchAttendanceMode('monthly')" id="btn-mode-monthly"
                        class="px-4 py-2 rounded-lg transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                        ⚡ บันทึกรายเดือนตามตารางสอน (กดครั้งเดียวครบ)
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <select id="att_academic_year" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 text-xs font-bold text-slate-700 cursor-pointer">
                        <!-- Loaded dynamically -->
                    </select>
                    <select id="att_semester" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 text-xs font-bold text-slate-700 cursor-pointer">
                        <option value="1">ภาคเรียนที่ 1</option>
                        <option value="2">ภาคเรียนที่ 2</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODE 1: DAILY ATTENDANCE (บันทึกรายวัน) -->
        <!-- ========================================== -->
        <div id="view-att-daily" class="space-y-6">
            <!-- Date Picker & Daily Schedule Header -->
            <div class="bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-slate-50 p-5 rounded-2xl border border-blue-100">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <label for="att_check_date" class="text-xs font-bold uppercase tracking-wider text-slate-600">เลือกวันที่บันทึก:</label>
                        <input type="date" id="att_check_date" value="<?= date('Y-m-d') ?>" 
                            class="px-4 py-2 bg-white border border-blue-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 text-sm font-bold text-slate-800 shadow-sm cursor-pointer">
                    </div>
                    <div id="daily-schedule-date-badge" class="text-sm font-bold text-blue-800 bg-white/80 px-4 py-1.5 rounded-xl border border-blue-100 shadow-xs">
                        <!-- Shows formatted Thai Date -->
                        กำลังโหลดวันที่...
                    </div>
                </div>

                <!-- Daily Schedule of the Day: Subjects and All Grade Levels Scheduled Today -->
                <div class="mt-4 pt-4 border-t border-blue-100/60">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                            <span>📚 รายวิชาและระดับชั้นทั้งหมดในวันนั้นตามตารางสอน</span>
                            <span id="daily-schedule-count" class="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-700">0 คาบ</span>
                        </h4>
                        <span class="text-xs text-slate-500 hidden sm:inline">คลิกที่คาบสอนเพื่อเลือกห้องเรียนและวิชาทันที</span>
                    </div>
                    <div id="daily-schedule-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Loaded from api/teacher/get_daily_schedule.php -->
                        <div class="col-span-full py-3 text-center text-xs text-slate-400">กำลังตรวจสอบตารางสอนของวันนี้...</div>
                    </div>
                </div>
            </div>

            <!-- All Classrooms Taught by Teacher -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                        เลือกระดับชั้น / ห้องเรียนที่คุณครูสอน (หรือทุกห้องเรียน):
                    </label>
                    <span id="classroom-count-badge" class="text-xs text-slate-400"></span>
                </div>
                <div id="attendance-classroom-list" class="flex flex-wrap gap-2">
                    <!-- Classrooms loaded here -->
                    <span class="text-xs text-slate-400 italic">กำลังโหลดรายการห้องเรียน...</span>
                </div>
            </div>

            <!-- Daily Attendance Main Table Container -->
            <div id="attendance-main-container" class="hidden space-y-6 pt-2">
                <!-- Subjects Tabs in this Classroom -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">วิชาที่สอนในห้องเรียนนี้ (<span id="active-classroom-label">-</span>)</h4>
                            <p class="text-xs text-slate-500">เลือกวิชาและคาบเรียนเพื่อบันทึก หรือคัดลอกการเช็คชื่อ</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="markAllStudentsDaily('present')" class="text-xs bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded-xl font-bold hover:bg-green-100 transition-all cursor-pointer">
                                ✨ มาครบทุกคน (100%)
                            </button>
                            <button type="button" onclick="applyAttendanceToAllSubjects()" class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-xl font-bold hover:bg-blue-100 transition-all cursor-pointer">
                                📋 คัดลอกไปทุกวิชาของวันนี้
                            </button>
                        </div>
                    </div>
                    <div id="attendance-subject-tabs" class="flex flex-wrap gap-2">
                        <!-- Subject tabs will be loaded here -->
                    </div>
                </div>

                <!-- Student Table -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            รายชื่อนักเรียน (<span id="daily-student-count">0</span> คน)
                        </span>
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> มา</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> ขาด</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> สาย</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> ป่วย</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> ลา</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-slate-500 border-b border-slate-200 text-xs bg-white">
                                    <th class="py-3 px-4 font-semibold w-16 text-center">เลขที่</th>
                                    <th class="py-3 px-4 font-semibold w-28">รหัสประจำตัว</th>
                                    <th class="py-3 px-4 font-semibold">ชื่อ-นามสกุล</th>
                                    <th class="py-3 px-4 font-semibold text-center w-72">สถานะการมาเรียน</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-table-body" class="divide-y divide-slate-100 bg-white">
                                <!-- Students will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <div class="text-xs text-slate-500">
                        * อย่าลืมกดปุ่มบันทึกการมาเรียนด้านขวาเพื่อบันทึกข้อมูลเข้าสู่ฐานข้อมูล
                    </div>
                    <button type="button" onclick="saveAttendance()" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-md shadow-blue-600/20 cursor-pointer flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>บันทึกการมาเรียน</span>
                    </button>
                </div>
            </div>

            <!-- Empty / No Subjects State -->
            <div id="attendance-empty-state" class="py-12 text-center bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
                </div>
                <h4 class="text-slate-800 font-bold">กรุณาเลือกระดับชั้น / ห้องเรียนด้านบน</h4>
                <p class="text-slate-500 text-xs mt-1">หรือคลิกคาบเรียนใน "รายวิชาและระดับชั้นในวันนั้น" เพื่อเริ่มเช็คชื่อ</p>
            </div>

            <div id="attendance-no-subjects-state" class="py-12 text-center bg-amber-50/30 rounded-2xl border border-dashed border-amber-200 hidden">
                <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h4 class="text-slate-800 font-bold">ไม่พบรายวิชาที่สอนในห้องเรียนนี้</h4>
                <p class="text-slate-500 text-xs mt-1">กรุณาตรวจสอบการมอบหมายงานสอนหรือตารางสอน</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODE 2: MONTHLY BULK ATTENDANCE (บันทึกรายเดือนตามตารางสอน กดครั้งเดียวครบ) -->
        <!-- ========================================================================= -->
        <div id="view-att-monthly" class="space-y-6 hidden">
            <!-- Monthly Filter & Selector Card -->
            <div class="bg-gradient-to-r from-emerald-50/70 via-teal-50/40 to-slate-50 p-5 rounded-2xl border border-emerald-100">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">⚡</span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">บันทึกการมาเรียนทั้งเดือนอ้างอิงจากตารางสอน (กดครั้งเดียวครบ)</h4>
                        <p class="text-xs text-slate-500">เลือกระดับชั้นและรายวิชา ระบบจะคำนวณวันและคาบตามตารางสอนในเดือนนั้นให้อัตโนมัติ ป้อนเฉพาะคนที่ขาด/ลา แล้วกดบันทึกครั้งเดียวได้ครบทุกคน</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Classroom Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">1. เลือกระดับชั้น/ห้องเรียน:</label>
                        <select id="monthly_classroom_select" onchange="onMonthlyClassroomChange()"
                            class="w-full px-3 py-2.5 bg-white border border-emerald-200 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500/20 text-xs font-bold text-slate-700 cursor-pointer">
                            <option value="">-- เลือกระดับชั้น/ห้องเรียน --</option>
                        </select>
                    </div>

                    <!-- Subject Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">2. เลือกรายวิชา:</label>
                        <select id="monthly_subject_select" onchange="loadMonthlyAttendanceData()"
                            class="w-full px-3 py-2.5 bg-white border border-emerald-200 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500/20 text-xs font-bold text-slate-700 cursor-pointer" disabled>
                            <option value="">-- กรุณาเลือกห้องเรียนก่อน --</option>
                        </select>
                    </div>

                    <!-- Month Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">3. เลือกเดือนที่ต้องการบันทึก:</label>
                        <select id="monthly_month_select" onchange="loadMonthlyAttendanceData()"
                            class="w-full px-3 py-2.5 bg-white border border-emerald-200 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500/20 text-xs font-bold text-slate-700 cursor-pointer">
                            <!-- Populated in JS: e.g. พ.ค., มิ.ย., ก.ค., ... -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Monthly Timetable Intelligence Summary -->
            <div id="monthly-summary-container" class="hidden space-y-6">
                <!-- Info Banner -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h5 id="monthly-subject-header" class="font-bold text-slate-800 text-sm">วิชา...</h5>
                                <span id="monthly-timetable-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">อ้างอิงจากตารางสอน</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                พบคาบเรียนในเดือนนี้จำนวน <span id="monthly-total-sessions" class="font-bold text-emerald-600">0</span> คาบ
                                (<span id="monthly-session-desc">คำนวณตามวันที่มีตารางสอน</span>)
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="showMonthlySessionsModal()" class="text-xs bg-slate-100 text-slate-700 px-3 py-2 rounded-xl font-bold hover:bg-slate-200 transition-all cursor-pointer flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>ดูวันที่และคาบเรียนทั้งหมด</span>
                        </button>
                        <button type="button" onclick="resetAllMonthlyPresent()" class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-2 rounded-xl font-bold hover:bg-emerald-100 transition-all cursor-pointer flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>ทุกคนมาครบ 100%</span>
                        </button>
                    </div>
                </div>

                <!-- Monthly Students Bulk Table -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            ตารางบันทึกเวลาเรียนรายบุคคล (<span id="monthly-student-count">0</span> คน)
                        </span>
                        <span class="text-xs text-slate-500">
                            * ป้อนจำนวนครั้งที่ ขาด / ลา / ป่วย (ถ้าไม่มีไม่ต้องแก้ไข ระบบจะบันทึกมาเรียน 100% ให้อัตโนมัติ)
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-slate-500 border-b border-slate-200 text-xs bg-white">
                                    <th class="py-3 px-3 font-semibold w-12 text-center">ที่</th>
                                    <th class="py-3 px-3 font-semibold w-24">รหัส</th>
                                    <th class="py-3 px-3 font-semibold">ชื่อ-นามสกุล</th>
                                    <th class="py-3 px-2 font-semibold text-center w-20 text-red-600">ขาด (ครั้ง)</th>
                                    <th class="py-3 px-2 font-semibold text-center w-20 text-purple-600">ลา (ครั้ง)</th>
                                    <th class="py-3 px-2 font-semibold text-center w-20 text-blue-600">ป่วย (ครั้ง)</th>
                                    <th class="py-3 px-2 font-semibold text-center w-24 text-emerald-600">มาเรียน (คาบ)</th>
                                    <th class="py-3 px-2 font-semibold text-center w-20">ร้อยละ (%)</th>
                                    <th class="py-3 px-2 font-semibold text-center w-20">ประเมิน</th>
                                </tr>
                            </thead>
                            <tbody id="monthly-table-body" class="divide-y divide-slate-100 bg-white text-xs">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- One-Click Save Bar -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 p-5 rounded-2xl text-white shadow-lg shadow-emerald-700/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-base flex items-center gap-2">
                            <span>⚡ บันทึกการมาเรียนของนักเรียนทั้งเดือนในคลิกเดียว</span>
                        </h4>
                        <p id="monthly-save-summary-text" class="text-xs text-emerald-100 mt-0.5">
                            จะทำการบันทึกเวลาเรียนลงในระบบจำนวน 0 คน x 0 คาบ = 0 รายการ
                        </p>
                    </div>
                    <button type="button" onclick="saveMonthlyAttendanceBulk()" id="btn-save-monthly"
                        class="bg-white text-emerald-700 hover:bg-emerald-50 px-8 py-3 rounded-xl font-bold transition-all shadow-md cursor-pointer flex items-center gap-2 text-sm whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>กดครั้งเดียวบันทึกมาเรียนได้ครบ</span>
                    </button>
                </div>
            </div>

            <!-- Monthly Initial Placeholder -->
            <div id="monthly-empty-state" class="py-12 text-center bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h4 class="text-slate-800 font-bold">กรุณาเลือกระดับชั้น/ห้องเรียน รายวิชา และเดือนที่ต้องการบันทึก</h4>
                <p class="text-slate-500 text-xs mt-1">ระบบจะดึงข้อมูลตารางสอนและรายชื่อนักเรียนมาให้คุณครูบันทึกได้ทันที</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Monthly Teaching Sessions -->
<div id="modal-monthly-sessions" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <h4 class="font-bold text-slate-800 text-sm">รายละเอียดวันและคาบสอนในเดือนนี้</h4>
            <button onclick="closeMonthlySessionsModal()" class="text-slate-400 hover:text-slate-600 font-bold text-lg p-1">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto space-y-2 text-xs" id="monthly-sessions-modal-list">
            <!-- List of sessions -->
        </div>
        <div class="p-3 border-t border-slate-100 bg-slate-50 text-right">
            <button onclick="closeMonthlySessionsModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-xl font-bold text-xs hover:bg-slate-300">ปิด</button>
        </div>
    </div>
</div>

<script>
    // State variables
    let currentAttendanceMode = 'daily'; // 'daily' | 'monthly'
    let attendanceClassrooms = [];
    let currentAttClassroom = null;
    let attStudents = [];
    let attSubjects = [];
    let activeAttSubject = null;
    let attendanceData = [];

    // Monthly state variables
    let monthlyData = null;
    let monthlyOverrides = {}; // { student_id: { absent_count: 0, leave_count: 0, sick_count: 0 } }

    function switchAttendanceMode(mode) {
        currentAttendanceMode = mode;
        const btnDaily = document.getElementById('btn-mode-daily');
        const btnMonthly = document.getElementById('btn-mode-monthly');
        const viewDaily = document.getElementById('view-att-daily');
        const viewMonthly = document.getElementById('view-att-monthly');

        if (mode === 'daily') {
            btnDaily.className = 'px-4 py-2 rounded-lg transition-all cursor-pointer bg-white text-blue-600 shadow-sm';
            btnMonthly.className = 'px-4 py-2 rounded-lg transition-all cursor-pointer text-slate-600 hover:text-slate-900';
            viewDaily.classList.remove('hidden');
            viewMonthly.classList.add('hidden');
        } else {
            btnDaily.className = 'px-4 py-2 rounded-lg transition-all cursor-pointer text-slate-600 hover:text-slate-900';
            btnMonthly.className = 'px-4 py-2 rounded-lg transition-all cursor-pointer bg-white text-emerald-600 shadow-sm';
            viewDaily.classList.add('hidden');
            viewMonthly.classList.remove('hidden');
            initMonthlyControls();
        }
    }

    // --- DAILY MODE LOGIC ---
    async function loadAttendanceClassrooms() {
        const yearEl = document.getElementById('att_academic_year');
        const semesterEl = document.getElementById('att_semester');
        if (!yearEl || !semesterEl) return;

        const year = yearEl.value || '2567';
        const semester = semesterEl.value || 1;

        try {
            // Load Daily Schedule of the selected date first
            loadDailySchedule();

            // Fetch ALL classrooms taught by this teacher across all levels
            const res = await fetch(`api/teacher/get_attendance_classrooms.php?academic_year=${year}&semester=${semester}`);
            const classrooms = await res.json();
            attendanceClassrooms = classrooms || [];

            const container = document.getElementById('attendance-classroom-list');
            const badge = document.getElementById('classroom-count-badge');
            if (badge) badge.innerText = `(${attendanceClassrooms.length} ห้องเรียน)`;

            if (!container) return;

            if (attendanceClassrooms.length === 0) {
                container.innerHTML = '<p class="text-sm text-red-500 font-bold italic">ไม่พบห้องเรียนที่ได้รับมอบหมายสอน</p>';
                return;
            }

            container.innerHTML = attendanceClassrooms.map(c => {
                const subCount = (c.subjects && Array.isArray(c.subjects)) ? c.subjects.length : 0;
                return `
                    <button onclick="selectAttendanceClassroom(${c.id}, '${c.level}/${c.room}')" 
                        id="btn-att-class-${c.id}"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold transition-all hover:border-blue-500 hover:text-blue-600 cursor-pointer bg-white flex items-center gap-1.5 shadow-xs">
                        <span>ชั้น ${c.level}/${c.room}</span>
                        ${subCount > 0 ? `<span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">${subCount} วิชา</span>` : ''}
                    </button>
                `;
            }).join('');

            // Also populate monthly classroom dropdown
            populateMonthlyClassroomSelect();

            if (currentAttClassroom) {
                selectAttendanceClassroom(currentAttClassroom.id, currentAttClassroom.name);
            }
        } catch (e) {
            console.error('Error loading attendance classrooms:', e);
        }
    }

    async function loadDailySchedule() {
        const checkDateEl = document.getElementById('att_check_date');
        const yearEl = document.getElementById('att_academic_year');
        const semesterEl = document.getElementById('att_semester');
        if (!checkDateEl || !yearEl) return;

        const dateVal = checkDateEl.value || '<?= date("Y-m-d") ?>';
        const yearVal = yearEl.value || '2567';
        const semVal = semesterEl ? semesterEl.value : 1;

        const dateBadge = document.getElementById('daily-schedule-date-badge');
        const scheduleContainer = document.getElementById('daily-schedule-list');
        const countBadge = document.getElementById('daily-schedule-count');

        try {
            const res = await fetch(`api/teacher/get_daily_schedule.php?check_date=${dateVal}&academic_year=${yearVal}&semester=${semVal}`);
            const data = await res.json();

            if (dateBadge && data.formatted_thai_date) {
                dateBadge.innerText = data.formatted_thai_date;
            }

            if (!scheduleContainer) return;

            const schedule = data.schedule || [];
            if (countBadge) countBadge.innerText = `${schedule.length} คาบ`;

            if (schedule.length === 0) {
                scheduleContainer.innerHTML = `
                    <div class="col-span-full p-4 rounded-xl bg-white/70 border border-blue-100 text-center text-xs text-slate-500">
                        <span>ไม่มีตารางสอนที่ระบุไว้ใน${data.day_name || 'วันนี้'} (สามารถเลือกระดับชั้นและห้องเรียนด้านล่างเพื่อเช็คชื่อได้)</span>
                    </div>
                `;
                return;
            }

            scheduleContainer.innerHTML = schedule.map(s => `
                <div onclick="selectDailyScheduleSlot(${s.classroom_id}, '${s.level}/${s.room}', '${s.subject_id}', ${s.period_number})"
                    class="p-3 bg-white rounded-xl border border-blue-200/80 hover:border-blue-500 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                คาบที่ ${s.period_number}
                            </span>
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                ชั้น ${s.classroom_name}
                            </span>
                        </div>
                        <h5 class="text-xs font-bold text-slate-800 line-clamp-1 group-hover:text-blue-600 transition-colors">
                            ${s.subject_code} ${s.subject_name}
                        </h5>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-blue-600 font-bold">
                        <span>เช็คชื่อคาบนี้</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            console.error('Error loading daily schedule:', e);
            if (scheduleContainer) {
                scheduleContainer.innerHTML = '<div class="col-span-full py-2 text-center text-xs text-slate-400">ไม่สามารถโหลดตารางสอนประจำวันได้</div>';
            }
        }
    }

    async function selectDailyScheduleSlot(classId, className, subjectId, periodNumber) {
        await selectAttendanceClassroom(classId, className);
        // Wait for subjects to load and then activate target subject
        setTimeout(() => {
            selectAttSubject(subjectId, periodNumber);
        }, 300);
    }

    async function selectAttendanceClassroom(id, name) {
        currentAttClassroom = { id, name };
        
        document.querySelectorAll('[id^="btn-att-class-"]').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-md', 'shadow-blue-600/20');
            btn.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        
        const activeBtn = document.getElementById(`btn-att-class-${id}`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            activeBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-md', 'shadow-blue-600/20');
        }

        const label = document.getElementById('active-classroom-label');
        if (label) label.innerText = `ชั้น ${name}`;

        document.getElementById('attendance-empty-state').classList.add('hidden');
        await loadAttendanceData();
    }

    async function loadAttendanceData() {
        if (!currentAttClassroom) return;

        const year = document.getElementById('att_academic_year').value;
        const semester = document.getElementById('att_semester').value;
        const checkDate = document.getElementById('att_check_date').value;

        try {
            const res = await fetch(`api/teacher/get_attendance_data.php?classroom_id=${currentAttClassroom.id}&academic_year=${year}&semester=${semester}&check_date=${checkDate}`);
            const result = await res.json();
            
            if (result.error) {
                alert(result.error);
                return;
            }

            attSubjects = result.subjects || [];
            attStudents = result.students || [];
            attendanceData = result.attendance || [];

            const stdCountBadge = document.getElementById('daily-student-count');
            if (stdCountBadge) stdCountBadge.innerText = attStudents.length;

            if (attSubjects.length === 0) {
                document.getElementById('attendance-main-container').classList.add('hidden');
                document.getElementById('attendance-no-subjects-state').classList.remove('hidden');
                return;
            }

            document.getElementById('attendance-no-subjects-state').classList.add('hidden');
            document.getElementById('attendance-main-container').classList.remove('hidden');

            renderSubjectTabs();
            // Select first subject by default if not set
            if (attSubjects.length > 0) {
                selectAttSubject(attSubjects[0].subject_id, attSubjects[0].period_number);
            }
        } catch (e) {
            console.error('Error loading attendance data:', e);
        }
    }

    function renderSubjectTabs() {
        const container = document.getElementById('attendance-subject-tabs');
        if (!container) return;
        container.innerHTML = attSubjects.map(s => {
            const sid = s.subject_id || 'none';
            const safeId = sid.toString().replace(':', '-');
            return `
                <button onclick="selectAttSubject('${sid}', ${s.period_number})" 
                    id="btn-att-sub-${safeId}-${s.period_number}"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold transition-all hover:border-blue-500 hover:text-blue-600 cursor-pointer bg-white flex items-center gap-1.5 shadow-2xs">
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600">คาบ ${s.period_number}</span>
                    <span>${s.subject_code || ''} ${s.subject_name || ''}</span>
                </button>
            `;
        }).join('');
    }

    function selectAttSubject(subjectId, period) {
        activeAttSubject = { subjectId, period };
        const sid = subjectId || 'none';
        const safeId = sid.toString().replace(':', '-');
        
        document.querySelectorAll('[id^="btn-att-sub-"]').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-xs');
            btn.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        
        const activeBtn = document.getElementById(`btn-att-sub-${safeId}-${period}`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            activeBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-xs');
        }

        renderAttendanceTable();
    }

    function renderAttendanceTable() {
        const tbody = document.getElementById('attendance-table-body');
        if (!tbody || !activeAttSubject) return;

        tbody.innerHTML = attStudents.map((s, index) => {
            const existing = attendanceData.find(a => a.student_id == s.id && a.subject_id == activeAttSubject.subjectId && a.period_number == activeAttSubject.period);
            const status = existing ? existing.status : 'present';

            return `
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3 px-4 text-slate-500 font-mono text-xs text-center">${index + 1}</td>
                    <td class="py-3 px-4 font-mono text-xs text-slate-600">${s.student_code || '-'}</td>
                    <td class="py-3 px-4 font-bold text-slate-800 text-xs">${s.prefix || ''}${s.name || ''} ${s.last_name || ''}</td>
                    <td class="py-3 px-4">
                        <div class="flex justify-center gap-1.5">
                            ${['present', 'absent', 'late', 'sick', 'leave'].map(st => {
                                const labels = { present: 'มา', absent: 'ขาด', late: 'สาย', sick: 'ป่วย', leave: 'ลา' };
                                const colors = { 
                                    present: 'peer-checked:bg-green-600 peer-checked:text-white text-green-700 border-green-300 hover:bg-green-50',
                                    absent: 'peer-checked:bg-red-600 peer-checked:text-white text-red-700 border-red-300 hover:bg-red-50',
                                    late: 'peer-checked:bg-amber-600 peer-checked:text-white text-amber-700 border-amber-300 hover:bg-amber-50',
                                    sick: 'peer-checked:bg-blue-600 peer-checked:text-white text-blue-700 border-blue-300 hover:bg-blue-50',
                                    leave: 'peer-checked:bg-purple-600 peer-checked:text-white text-purple-700 border-purple-300 hover:bg-purple-50'
                                };
                                return `
                                    <label class="cursor-pointer">
                                        <input type="radio" name="status-${s.id}-${activeAttSubject.subjectId}-${activeAttSubject.period}" value="${st}" ${status === st ? 'checked' : ''} 
                                            onchange="updateLocalAttendance(${s.id}, '${st}')"
                                            class="hidden peer">
                                        <span class="px-2.5 py-1 rounded-lg border text-[11px] font-bold transition-all block ${colors[st]}">
                                            ${labels[st]}
                                        </span>
                                    </label>
                                `;
                            }).join('')}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateLocalAttendance(studentId, status) {
        if (!activeAttSubject) return;
        const existingIndex = attendanceData.findIndex(a => a.student_id == studentId && a.subject_id == activeAttSubject.subjectId && a.period_number == activeAttSubject.period);
        if (existingIndex > -1) {
            attendanceData[existingIndex].status = status;
        } else {
            attendanceData.push({
                student_id: studentId,
                subject_id: activeAttSubject.subjectId,
                period_number: activeAttSubject.period,
                status: status
            });
        }
    }

    function markAllStudentsDaily(status) {
        if (!activeAttSubject || !attStudents.length) return;
        attStudents.forEach(s => {
            updateLocalAttendance(s.id, status);
        });
        renderAttendanceTable();
    }

    function applyAttendanceToAllSubjects() {
        if (!activeAttSubject || attSubjects.length <= 1) return;
        
        if (confirm('ต้องการคัดลอกสถานะการมาเรียนของวิชานี้ไปยังทุกวิชาที่สอนในห้องนี้ของวันนี้ใช่หรือไม่?')) {
            const currentStatuses = attStudents.map(s => {
                const found = attendanceData.find(a => a.student_id == s.id && a.subject_id == activeAttSubject.subjectId && a.period_number == activeAttSubject.period);
                return { student_id: s.id, status: found ? found.status : 'present' };
            });

            attSubjects.forEach(sub => {
                if (sub.subject_id == activeAttSubject.subjectId && sub.period_number == activeAttSubject.period) return;
                
                currentStatuses.forEach(cs => {
                    const idx = attendanceData.findIndex(a => a.student_id == cs.student_id && a.subject_id == sub.subject_id && a.period_number == sub.period_number);
                    if (idx > -1) {
                        attendanceData[idx].status = cs.status;
                    } else {
                        attendanceData.push({
                            student_id: cs.student_id,
                            subject_id: sub.subject_id,
                            period_number: sub.period_number,
                            status: cs.status
                        });
                    }
                });
            });
            
            alert('คัดลอกข้อมูลเรียบร้อยแล้ว อย่าลืมกดบันทึกการมาเรียนเพื่อบันทึกข้อมูลทั้งหมด');
        }
    }

    async function saveAttendance() {
        if (!currentAttClassroom) return;

        const records = [];
        attSubjects.forEach(sub => {
            attStudents.forEach(s => {
                const found = attendanceData.find(a => a.student_id == s.id && a.subject_id == sub.subject_id && a.period_number == sub.period_number);
                records.push({
                    student_id: s.id,
                    subject_id: sub.subject_id,
                    period_number: sub.period_number,
                    status: found ? found.status : 'present'
                });
            });
        });

        const payload = {
            classroom_id: currentAttClassroom.id,
            academic_year: document.getElementById('att_academic_year').value,
            semester: document.getElementById('att_semester').value,
            check_date: document.getElementById('att_check_date').value,
            records: records
        };

        try {
            const res = await fetch('api/teacher/save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadAttendanceData();
            } else {
                alert(result.error || 'เกิดข้อผิดพลาดในการบันทึก');
            }
        } catch (e) {
            console.error('Error saving attendance:', e);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        }
    }

    // --- MONTHLY BULK ATTENDANCE LOGIC ---
    function initMonthlyControls() {
        populateMonthlyMonthSelect();
        populateMonthlyClassroomSelect();
    }

    function populateMonthlyMonthSelect() {
        const monthSelect = document.getElementById('monthly_month_select');
        if (!monthSelect) return;

        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1; // 1-12

        // Thai school year months (May to March)
        const monthsList = [
            { num: 5, name: 'พฤษภาคม (05)' },
            { num: 6, name: 'มิถุนายน (06)' },
            { num: 7, name: 'กรกฎาคม (07)' },
            { num: 8, name: 'สิงหาคม (08)' },
            { num: 9, name: 'กันยายน (09)' },
            { num: 10, name: 'ตุลาคม (10)' },
            { num: 11, name: 'พฤศจิกายน (11)' },
            { num: 12, name: 'ธันวาคม (12)' },
            { num: 1, name: 'มกราคม (01)' },
            { num: 2, name: 'กุมภาพันธ์ (02)' },
            { num: 3, name: 'มีนาคม (03)' }
        ];

        monthSelect.innerHTML = monthsList.map(m => {
            const padMonth = m.num.toString().padStart(2, '0');
            // If month is Jan-Mar, it belongs to the following calendar year
            const targetYear = (m.num <= 3) ? currentYear : currentYear;
            const val = `${targetYear}-${padMonth}`;
            const isSelected = (m.num === currentMonth);
            return `<option value="${val}" ${isSelected ? 'selected' : ''}>เดือน${m.name} พ.ศ. ${targetYear + 543}</option>`;
        }).join('');
    }

    function populateMonthlyClassroomSelect() {
        const clsSelect = document.getElementById('monthly_classroom_select');
        if (!clsSelect) return;

        clsSelect.innerHTML = '<option value="">-- เลือกระดับชั้น/ห้องเรียน --</option>' + 
            attendanceClassrooms.map(c => `
                <option value="${c.id}" ${currentAttClassroom && currentAttClassroom.id === c.id ? 'selected' : ''}>
                    ชั้น ${c.level}/${c.room}
                </option>
            `).join('');

        if (currentAttClassroom) {
            clsSelect.value = currentAttClassroom.id;
            onMonthlyClassroomChange();
        }
    }

    function onMonthlyClassroomChange() {
        const clsSelect = document.getElementById('monthly_classroom_select');
        const subSelect = document.getElementById('monthly_subject_select');
        if (!clsSelect || !subSelect) return;

        const classId = parseInt(clsSelect.value);
        if (!classId) {
            subSelect.innerHTML = '<option value="">-- กรุณาเลือกห้องเรียนก่อน --</option>';
            subSelect.disabled = true;
            document.getElementById('monthly-summary-container').classList.add('hidden');
            document.getElementById('monthly-empty-state').classList.remove('hidden');
            return;
        }

        const foundClass = attendanceClassrooms.find(c => c.id == classId);
        if (!foundClass || !foundClass.subjects || foundClass.subjects.length === 0) {
            subSelect.innerHTML = '<option value="">-- ไม่พบรายวิชาในห้องนี้ --</option>';
            subSelect.disabled = true;
            return;
        }

        subSelect.disabled = false;
        subSelect.innerHTML = '<option value="">-- เลือกรายวิชา --</option>' + 
            foundClass.subjects.map(s => `
                <option value="${s.subject_id}">
                    ${s.subject_code ? s.subject_code + ' - ' : ''}${s.subject_name}
                </option>
            `).join('');

        if (foundClass.subjects.length > 0) {
            subSelect.value = foundClass.subjects[0].subject_id;
            loadMonthlyAttendanceData();
        }
    }

    async function loadMonthlyAttendanceData() {
        const classSelect = document.getElementById('monthly_classroom_select');
        const subSelect = document.getElementById('monthly_subject_select');
        const monthSelect = document.getElementById('monthly_month_select');
        const yearEl = document.getElementById('att_academic_year');
        const semesterEl = document.getElementById('att_semester');

        if (!classSelect || !subSelect || !monthSelect) return;

        const classId = classSelect.value;
        const subId = subSelect.value;
        const monthVal = monthSelect.value;
        const yearVal = yearEl ? yearEl.value : '2567';
        const semVal = semesterEl ? semesterEl.value : 1;

        if (!classId || !subId || !monthVal) {
            document.getElementById('monthly-summary-container').classList.add('hidden');
            document.getElementById('monthly-empty-state').classList.remove('hidden');
            return;
        }

        try {
            const url = `api/teacher/get_monthly_attendance_data.php?classroom_id=${classId}&subject_id=${encodeURIComponent(subId)}&academic_year=${yearVal}&semester=${semVal}&month=${monthVal}`;
            const res = await fetch(url);
            monthlyData = await res.json();

            if (monthlyData.error) {
                alert(monthlyData.error);
                return;
            }

            document.getElementById('monthly-empty-state').classList.add('hidden');
            document.getElementById('monthly-summary-container').classList.remove('hidden');

            // Render monthly summary
            const subHeader = document.getElementById('monthly-subject-header');
            if (subHeader && monthlyData.subject) {
                subHeader.innerText = `${monthlyData.subject.code || ''} ${monthlyData.subject.name || ''} (ชั้น ${monthlyData.classroom.level}/${monthlyData.classroom.room})`;
            }

            const totalSessionsEl = document.getElementById('monthly-total-sessions');
            if (totalSessionsEl) totalSessionsEl.innerText = monthlyData.total_sessions || 0;

            const badge = document.getElementById('monthly-timetable-badge');
            const descEl = document.getElementById('monthly-session-desc');
            if (badge && descEl) {
                if (monthlyData.has_timetable) {
                    badge.innerText = 'อ้างอิงจากตารางสอน';
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700';
                    descEl.innerText = `คำนวณตามวันที่มีตารางสอนจริงในเดือนนี้ (${monthlyData.total_sessions} คาบ)`;
                } else {
                    badge.innerText = 'คำนวณจากวันเปิดเรียน (จันทร์-ศุกร์)';
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700';
                    descEl.innerText = 'ยังไม่ได้จัดตารางสอน ระบบคำนวณจากวันจันทร์-ศุกร์';
                }
            }

            const stdCountBadge = document.getElementById('monthly-student-count');
            if (stdCountBadge) stdCountBadge.innerText = (monthlyData.students || []).length;

            // Initialize overrides based on existing attendance
            monthlyOverrides = {};
            const summary = monthlyData.student_summary || {};
            (monthlyData.students || []).forEach(s => {
                const prev = summary[s.id] || {};
                monthlyOverrides[s.id] = {
                    absent_count: prev.absent || 0,
                    leave_count: prev.leave || 0,
                    sick_count: prev.sick || 0
                };
            });

            renderMonthlyTable();
            updateMonthlySaveSummary();
        } catch (e) {
            console.error('Error loading monthly attendance:', e);
        }
    }

    function renderMonthlyTable() {
        const tbody = document.getElementById('monthly-table-body');
        if (!tbody || !monthlyData) return;

        const students = monthlyData.students || [];
        const totalSessions = monthlyData.total_sessions || 0;

        tbody.innerHTML = students.map((s, idx) => {
            const ov = monthlyOverrides[s.id] || { absent_count: 0, leave_count: 0, sick_count: 0 };
            const absent = ov.absent_count;
            const leave = ov.leave_count;
            const sick = ov.sick_count;
            const nonPresent = absent + leave + sick;
            const present = Math.max(0, totalSessions - nonPresent);
            const percent = totalSessions > 0 ? ((present / totalSessions) * 100).toFixed(1) : '100.0';
            const isPass = parseFloat(percent) >= 80.0;

            return `
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono">${idx + 1}</td>
                    <td class="py-2.5 px-3 font-mono text-slate-600">${s.student_code || '-'}</td>
                    <td class="py-2.5 px-3 font-bold text-slate-800">${s.prefix || ''}${s.name || ''} ${s.last_name || ''}</td>
                    <td class="py-2.5 px-2 text-center">
                        <input type="number" min="0" max="${totalSessions}" value="${absent}" 
                            onchange="updateMonthlyOverride(${s.id}, 'absent_count', this.value)"
                            class="w-14 px-2 py-1 text-center font-bold text-red-600 bg-red-50/50 border border-red-200 rounded-lg outline-none focus:ring-1 focus:ring-red-500">
                    </td>
                    <td class="py-2.5 px-2 text-center">
                        <input type="number" min="0" max="${totalSessions}" value="${leave}" 
                            onchange="updateMonthlyOverride(${s.id}, 'leave_count', this.value)"
                            class="w-14 px-2 py-1 text-center font-bold text-purple-600 bg-purple-50/50 border border-purple-200 rounded-lg outline-none focus:ring-1 focus:ring-purple-500">
                    </td>
                    <td class="py-2.5 px-2 text-center">
                        <input type="number" min="0" max="${totalSessions}" value="${sick}" 
                            onchange="updateMonthlyOverride(${s.id}, 'sick_count', this.value)"
                            class="w-14 px-2 py-1 text-center font-bold text-blue-600 bg-blue-50/50 border border-blue-200 rounded-lg outline-none focus:ring-1 focus:ring-blue-500">
                    </td>
                    <td class="py-2.5 px-2 text-center font-bold text-emerald-700">
                        <span id="monthly-present-${s.id}">${present}</span> / ${totalSessions}
                    </td>
                    <td class="py-2.5 px-2 text-center font-mono font-bold">
                        <span id="monthly-percent-${s.id}" class="${isPass ? 'text-emerald-600' : 'text-red-600'}">${percent}%</span>
                    </td>
                    <td class="py-2.5 px-2 text-center">
                        <span id="monthly-pass-${s.id}" class="px-2 py-0.5 rounded-full text-[10px] font-bold ${isPass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            ${isPass ? 'ผ่าน' : 'ไม่ผ่าน'}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateMonthlyOverride(studentId, key, val) {
        val = parseInt(val) || 0;
        if (!monthlyOverrides[studentId]) {
            monthlyOverrides[studentId] = { absent_count: 0, leave_count: 0, sick_count: 0 };
        }
        monthlyOverrides[studentId][key] = Math.max(0, val);

        // Update single row calculations dynamically
        if (!monthlyData) return;
        const totalSessions = monthlyData.total_sessions || 0;
        const ov = monthlyOverrides[studentId];
        const nonPresent = ov.absent_count + ov.leave_count + ov.sick_count;
        const present = Math.max(0, totalSessions - nonPresent);
        const percent = totalSessions > 0 ? ((present / totalSessions) * 100).toFixed(1) : '100.0';
        const isPass = parseFloat(percent) >= 80.0;

        const presentEl = document.getElementById(`monthly-present-${studentId}`);
        if (presentEl) presentEl.innerText = present;

        const percentEl = document.getElementById(`monthly-percent-${studentId}`);
        if (percentEl) {
            percentEl.innerText = `${percent}%`;
            percentEl.className = isPass ? 'text-emerald-600' : 'text-red-600';
        }

        const passEl = document.getElementById(`monthly-pass-${studentId}`);
        if (passEl) {
            passEl.innerText = isPass ? 'ผ่าน' : 'ไม่ผ่าน';
            passEl.className = `px-2 py-0.5 rounded-full text-[10px] font-bold ${isPass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
        }

        updateMonthlySaveSummary();
    }

    function resetAllMonthlyPresent() {
        if (!monthlyData || !monthlyData.students) return;
        monthlyData.students.forEach(s => {
            monthlyOverrides[s.id] = { absent_count: 0, leave_count: 0, sick_count: 0 };
        });
        renderMonthlyTable();
        updateMonthlySaveSummary();
    }

    function updateMonthlySaveSummary() {
        if (!monthlyData) return;
        const studentCount = (monthlyData.students || []).length;
        const sessionCount = monthlyData.total_sessions || 0;
        const totalRecords = studentCount * sessionCount;

        const summaryText = document.getElementById('monthly-save-summary-text');
        if (summaryText) {
            summaryText.innerText = `จะทำการบันทึกเวลาเรียนลงในระบบจำนวน ${studentCount} คน x ${sessionCount} คาบ = ${totalRecords.toLocaleString()} รายการ`;
        }
    }

    async function saveMonthlyAttendanceBulk() {
        if (!monthlyData || !monthlyData.teaching_sessions || !monthlyData.students) {
            alert('ไม่พบข้อมูลสำหรับบันทึก');
            return;
        }

        const studentCount = monthlyData.students.length;
        const sessionCount = monthlyData.teaching_sessions.length;

        if (sessionCount === 0) {
            alert('ไม่พบคาบเรียนในเดือนนี้ ไม่สามารถบันทึกได้');
            return;
        }

        const btn = document.getElementById('btn-save-monthly');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'กำลังบันทึกข้อมูล...';
        }

        const payload = {
            classroom_id: monthlyData.classroom.id,
            subject_id: monthlyData.subject.id,
            academic_year: document.getElementById('att_academic_year').value,
            semester: document.getElementById('att_semester').value,
            month: monthlyData.month,
            sessions: monthlyData.teaching_sessions.map(s => ({ date: s.date, period_number: s.period_number })),
            students: monthlyData.students.map(s => ({ id: s.id, student_code: s.student_code })),
            overrides: monthlyOverrides
        };

        try {
            const res = await fetch('api/teacher/save_monthly_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success' || result.message) {
                alert(`✅ ${result.message || 'บันทึกเวลาเรียนทั้งเดือนเรียบร้อยแล้ว!'}`);
                await loadMonthlyAttendanceData();
            } else {
                alert(result.error || 'เกิดข้อผิดพลาดในการบันทึก');
            }
        } catch (e) {
            console.error('Error saving monthly attendance:', e);
            alert('เกิดข้อผิดพลาดในการส่งข้อมูล: ' + e.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>กดครั้งเดียวบันทึกมาเรียนได้ครบ</span>
                `;
            }
        }
    }

    function showMonthlySessionsModal() {
        if (!monthlyData || !monthlyData.teaching_sessions) return;
        const modal = document.getElementById('modal-monthly-sessions');
        const list = document.getElementById('monthly-sessions-modal-list');
        if (!modal || !list) return;

        list.innerHTML = monthlyData.teaching_sessions.map((s, idx) => `
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-[10px]">${idx + 1}</span>
                    <span class="font-bold text-slate-800">${s.date}</span>
                    <span class="text-slate-500">(${s.day_name})</span>
                </div>
                <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 font-bold">คาบที่ ${s.period_number}</span>
            </div>
        `).join('');

        modal.classList.remove('hidden');
    }

    function closeMonthlySessionsModal() {
        const modal = document.getElementById('modal-monthly-sessions');
        if (modal) modal.classList.add('hidden');
    }

    // --- EVENT LISTENERS & INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', () => {
        const yearEl = document.getElementById('att_academic_year');
        if (yearEl) yearEl.addEventListener('change', () => {
            loadAttendanceClassrooms();
            if (currentAttendanceMode === 'monthly') loadMonthlyAttendanceData();
        });
        const semEl = document.getElementById('att_semester');
        if (semEl) semEl.addEventListener('change', () => {
            loadAttendanceClassrooms();
            if (currentAttendanceMode === 'monthly') loadMonthlyAttendanceData();
        });
        const dateEl = document.getElementById('att_check_date');
        if (dateEl) dateEl.addEventListener('change', () => {
            loadDailySchedule();
            if (currentAttClassroom) loadAttendanceData();
        });
    });

    async function initAttendanceSection() {
        try {
            const res = await fetch('api/academic/get_academic_years.php');
            const years = await res.json();
            const el = document.getElementById('att_academic_year');
            if (el && Array.isArray(years) && years.length > 0) {
                const sortedYears = [...years].sort((a, b) => b.year - a.year);
                el.innerHTML = sortedYears.map(y => `<option value="${y.year}" ${Number(y.is_current) === 1 ? 'selected' : ''}>ปีการศึกษา ${y.year}</option>`).join('');
                const current = sortedYears.find(y => Number(y.is_current) === 1);
                if (current) el.value = current.year;
            }
            await loadAttendanceClassrooms();
        } catch (e) {
            console.error('Error initializing attendance section:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', initAttendanceSection);
</script>
