/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { 
  LayoutDashboard, 
  Users, 
  BookOpen, 
  FileText, 
  AlertCircle,
  Printer,
  GraduationCap,
  School,
  UserPlus,
  Settings,
  Calendar,
  CheckCircle,
  ClipboardList,
  Lock,
  ChevronRight,
  Menu,
  X,
  Bell,
  User,
  Search,
  LogOut,
  TrendingUp,
  Award,
  BookMarked
} from 'lucide-react';

// --- Reusable Components ---

const StatCard = ({ title, value, icon: Icon, colorClass, subtitle }: any) => (
  <div className={`stat-card ${colorClass} h-full`}>
    <div className="relative z-10">
      <p className="text-white/80 text-sm font-medium mb-1">{title}</p>
      <h2 className="text-3xl font-bold mb-1">{value}</h2>
      {subtitle && <p className="text-white/60 text-xs">{subtitle}</p>}
    </div>
    <Icon size={80} className="stat-card-icon" />
  </div>
);

export default function App() {
  const [user, setUser] = useState<any>(null);
  const [students, setStudents] = useState<any[]>([]);
  const [schools, setSchools] = useState<any[]>([]);
  const [teachers, setTeachers] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showPasswordChange, setShowPasswordChange] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [newSchool, setNewSchool] = useState({ code: '', name: '', province: '' });
  const [newStudent, setNewStudent] = useState({ id: '', name: '', level: 'ป.1', photo: null });

  const [loginData, setLoginData] = useState({ username: '', password: '' });
  const [regData, setRegData] = useState({ smissCode: '', nationalId: '', name: '', position: '', role: 'teacher' });
  const [isRegistering, setIsRegistering] = useState(false);
  const [newPassword, setNewPassword] = useState('');
  const [pendingUsers, setPendingUsers] = useState<any[]>([]);

  // ฟังก์ชัน Login จริง
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(loginData)
      });
      const data = await res.json();
      if (res.ok) {
        setUser(data);
        if (data.is_first_login) {
          setShowPasswordChange(true);
        }
        fetchInitialData(data);
      } else {
        alert(data.error);
      }
    } catch (err) {
      alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await fetch('/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(regData)
      });
      const data = await res.json();
      if (res.ok) {
        alert(data.message);
        setIsRegistering(false);
      } else {
        alert(data.error);
      }
    } catch (err) {
      alert('เกิดข้อผิดพลาดในการสมัครสมาชิก');
    } finally {
      setLoading(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword.length < 6) {
      alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
      return;
    }
    try {
      const res = await fetch('/api/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: user.id, newPassword })
      });
      if (res.ok) {
        alert('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        setShowPasswordChange(false);
        setUser({ ...user, is_first_login: false });
      }
    } catch (err) {
      alert('ไม่สามารถเปลี่ยนรหัสผ่านได้');
    }
  };

  const fetchInitialData = async (currentUser: any) => {
    try {
      if (currentUser.role === 'super_admin') {
        const res = await fetch('/api/schools');
        setSchools(await res.json());
        
        const pendingRes = await fetch('/api/admin/pending-users?role=super_admin');
        setPendingUsers(await pendingRes.json());
      }
      
      if (currentUser.role === 'admin') {
        const pendingRes = await fetch(`/api/admin/pending-users?role=admin&schoolId=${currentUser.school_id}`);
        setPendingUsers(await pendingRes.json());
      }

      const teacherRes = await fetch('/api/teachers');
      setTeachers(await teacherRes.json());

      const studentRes = await fetch('/api/students');
      setStudents(await studentRes.json());
    } catch (err) {
      console.error('Fetch Error:', err);
    }
  };

  const handleApprove = async (userId: number) => {
    try {
      const res = await fetch('/api/admin/approve-user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      if (res.ok) {
        alert('อนุมัติเรียบร้อย');
        fetchInitialData(user);
      }
    } catch (err) {
      alert('เกิดข้อผิดพลาดในการอนุมัติ');
    }
  };

  const handleAddSchool = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newSchool.code.length !== 8) {
      alert('รหัสโรงเรียนต้องมี 8 หลัก');
      return;
    }
    try {
      const res = await fetch('/api/schools', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newSchool)
      });
      if (res.ok) {
        // Refresh list
        const refreshRes = await fetch('/api/schools');
        const refreshData = await refreshRes.json();
        setSchools(refreshData);
        setNewSchool({ code: '', name: '', province: '' });
      }
    } catch (err) {
      console.error('Add School Error:', err);
    }
  };

  const handleAddStudent = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch('/api/students', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: newStudent.name,
          student_code: newStudent.id,
          level: newStudent.level,
          school_id: 1 // Default for demo
        })
      });
      if (res.ok) {
        // Refresh list
        const refreshRes = await fetch('/api/students');
        const refreshData = await refreshRes.json();
        setStudents(refreshData);
        setNewStudent({ id: '', name: '', level: 'ป.1', photo: null });
      }
    } catch (err) {
      console.error('Add Student Error:', err);
    }
  };

  const promoteStudents = async () => {
    if (confirm('คุณต้องการเลื่อนชั้นนักเรียนทั้งหมดใช่หรือไม่?')) {
      try {
        const studentIds = students.map(s => s.id);
        const res = await fetch('/api/students/promote', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            studentIds,
            nextLevel: 'ป.2' // Simplified for demo
          })
        });
        if (res.ok) {
          const refreshRes = await fetch('/api/students');
          const refreshData = await refreshRes.json();
          setStudents(refreshData);
        }
      } catch (err) {
        console.error('Promote Error:', err);
      }
    }
  };

  const handleLogout = () => {
    setUser(null);
    setActiveTab('dashboard');
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-slate-500 font-medium">กำลังเข้าสู่ระบบ...</p>
        </div>
      </div>
    );
  }

  // --- Login Page ---
  if (!user) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0f172a] p-4">
        <div className="w-full max-w-md">
          <div className="text-center mb-8">
            <div className="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-900/40">
              <GraduationCap size={40} className="text-white" />
            </div>
            <h2 className="text-3xl font-bold text-white mb-2">ระบบวัดผล ปพ.</h2>
            <p className="text-slate-400">{isRegistering ? 'สมัครขอใช้งานระบบ' : 'เข้าสู่ระบบเพื่อจัดการข้อมูลการเรียน'}</p>
          </div>
          
          <div className="bg-white rounded-3xl p-8 shadow-2xl">
            {!isRegistering ? (
              <form onSubmit={handleLogin} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (เลขบัตรประชาชน)</label>
                  <input 
                    type="text" 
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="กรอกชื่อผู้ใช้งาน"
                    value={loginData.username}
                    onChange={(e) => setLoginData({...loginData, username: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
                  <input 
                    type="password" 
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="กรอกรหัสผ่าน"
                    value={loginData.password}
                    onChange={(e) => setLoginData({...loginData, password: e.target.value})}
                  />
                </div>
                <button type="submit" className="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all flex items-center justify-center">
                  เข้าสู่ระบบ
                </button>
                <div className="text-center pt-4">
                  <button type="button" onClick={() => setIsRegistering(true)} className="text-blue-600 font-medium hover:underline">
                    สมัครขอใช้งานระบบ
                  </button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleRegister} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">รหัส SMISS 8 หลัก</label>
                  <input 
                    type="text" 
                    maxLength={8}
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="รหัสโรงเรียน"
                    value={regData.smissCode}
                    onChange={(e) => setRegData({...regData, smissCode: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">เลขประจำตัวประชาชน</label>
                  <input 
                    type="text" 
                    maxLength={13}
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="เลข 13 หลัก"
                    value={regData.nationalId}
                    onChange={(e) => setRegData({...regData, nationalId: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                  <input 
                    type="text" 
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="ชื่อ-นามสกุล"
                    value={regData.name}
                    onChange={(e) => setRegData({...regData, name: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">ตำแหน่ง</label>
                  <input 
                    type="text" 
                    required
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="เช่น ครูผู้ช่วย, ผู้อำนวยการ"
                    value={regData.position}
                    onChange={(e) => setRegData({...regData, position: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">ประเภทการสมัคร</label>
                  <select 
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    value={regData.role}
                    onChange={(e) => setRegData({...regData, role: e.target.value})}
                  >
                    <option value="teacher">คุณครูผู้สอน</option>
                    <option value="admin">ตัวแทนโรงเรียน (Admin)</option>
                  </select>
                </div>
                <button type="submit" className="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all flex items-center justify-center">
                  ส่งคำขอสมัครสมาชิก
                </button>
                <div className="text-center pt-4">
                  <button type="button" onClick={() => setIsRegistering(false)} className="text-slate-500 font-medium hover:underline">
                    กลับไปหน้าเข้าสู่ระบบ
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      </div>
    );
  }

  // --- Password Change Modal ---
  if (showPasswordChange) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 z-[100] fixed inset-0">
        <div className="bg-white rounded-3xl p-8 shadow-2xl w-full max-w-md">
          <div className="text-center mb-6">
            <div className="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-orange-600">
              <Lock size={32} />
            </div>
            <h3 className="text-2xl font-bold text-slate-900">เปลี่ยนรหัสผ่านใหม่</h3>
            <p className="text-slate-500">เนื่องจากเป็นการเข้าใช้งานครั้งแรก กรุณาตั้งรหัสผ่านใหม่เพื่อความปลอดภัย</p>
          </div>
          <form onSubmit={handleChangePassword} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">รหัสผ่านใหม่</label>
              <input 
                type="password" 
                required
                minLength={6}
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                placeholder="อย่างน้อย 6 ตัวอักษร"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
              />
            </div>
            <button type="submit" className="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all">
              ยืนยันการเปลี่ยนรหัสผ่าน
            </button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex bg-[#f8fafc]">
      {/* Sidebar */}
      <aside className={`fixed left-0 top-0 h-full w-72 bg-[#1e293b] z-50 transition-transform duration-300 transform ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'} flex flex-col shadow-2xl`}>
        <div className="p-6 flex items-center border-b border-slate-800">
          <div className="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-900/40">
            <School size={24} />
          </div>
          <div className="ms-3">
            <h1 className="text-lg font-bold text-white leading-tight">ระบบทะเบียน</h1>
            <p className="text-[10px] text-slate-400 uppercase tracking-widest font-bold">School Management</p>
          </div>
        </div>

        <nav className="flex-1 px-4 py-6 space-y-1 overflow-y-auto no-scrollbar">
          <button onClick={() => setActiveTab('dashboard')} className={`sidebar-item w-full ${activeTab === 'dashboard' && 'active'}`}>
            <LayoutDashboard size={20} className="shrink-0" />
            <span className="ms-3">Dashboard</span>
          </button>

          {user.role === 'super_admin' && (
            <>
              <p className="px-4 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">การจัดการ</p>
              <button onClick={() => setActiveTab('manage_schools')} className={`sidebar-item w-full ${activeTab === 'manage_schools' && 'active'}`}>
                <School size={20} className="shrink-0" />
                <span className="ms-3">จัดการโรงเรียน</span>
              </button>
              <button className="sidebar-item w-full">
                <Users size={20} className="shrink-0" />
                <span className="ms-3">จัดการผู้ใช้</span>
              </button>
            </>
          )}

          {user.role === 'admin' && (
            <>
              <p className="px-4 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                การจัดการโรงเรียน
              </p>
              <button onClick={() => setActiveTab('manage_teachers')} className={`sidebar-item w-full ${activeTab === 'manage_teachers' && 'active'}`}>
                <UserPlus size={20} className="shrink-0" />
                <span className="ms-3">จัดการครู</span>
              </button>
              <button onClick={() => setActiveTab('manage_students')} className={`sidebar-item w-full ${activeTab === 'manage_students' && 'active'}`}>
                <Users size={20} className="shrink-0" />
                <span className="ms-3">จัดการนักเรียน</span>
              </button>
              <button onClick={() => setActiveTab('manage_curriculum')} className={`sidebar-item w-full ${activeTab === 'manage_curriculum' && 'active'}`}>
                <BookOpen size={20} className="shrink-0" />
                <span className="ms-3">จัดการหลักสูตร</span>
              </button>
            </>
          )}

          {(user.role === 'admin' || user.role === 'teacher') && (
            <>
              <p className="px-4 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                งานครูผู้สอน
              </p>
              <button onClick={() => setActiveTab('grading')} className={`sidebar-item w-full ${activeTab === 'grading' && 'active'}`}>
                <FileText size={20} className="shrink-0" />
                <span className="ms-3">บันทึกคะแนน</span>
              </button>
              <button onClick={() => setActiveTab('attendance')} className={`sidebar-item w-full ${activeTab === 'attendance' && 'active'}`}>
                <Calendar size={20} className="shrink-0" />
                <span className="ms-3">เวลาเรียน</span>
              </button>
              <button onClick={() => setActiveTab('characteristics')} className={`sidebar-item w-full ${activeTab === 'characteristics' && 'active'}`}>
                <CheckCircle size={20} className="shrink-0" />
                <span className="ms-3">คุณลักษณะฯ</span>
              </button>
              <button onClick={() => setActiveTab('analytical')} className={`sidebar-item w-full ${activeTab === 'analytical' && 'active'}`}>
                <ClipboardList size={20} className="shrink-0" />
                <span className="ms-3">การอ่าน คิดวิเคราะห์</span>
              </button>
              
              <p className="px-4 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                งานครูประจำชั้น
              </p>
              <button onClick={() => setActiveTab('papor6')} className={`sidebar-item w-full ${activeTab === 'papor6' && 'active'}`}>
                <Printer size={20} className="shrink-0" />
                <span className="ms-3">ปพ.6 / รายงานผล</span>
              </button>
            </>
          )}
        </nav>

        <div className="p-4 mt-auto border-t border-slate-800">
          <button onClick={handleLogout} className="flex items-center w-full px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl transition-colors">
            <LogOut size={20} />
            <span className="ms-3 font-semibold">ออกจากระบบ</span>
          </button>
        </div>
      </aside>

      {/* Main Content Area */}
      <main className={`flex-1 ${isSidebarOpen ? 'lg:ms-72' : 'ms-0'} transition-all duration-300 min-h-screen flex flex-col no-scrollbar`}>
        {/* Header */}
        <header className="h-16 bg-white border-b border-slate-100 flex items-center justify-between px-6 sticky top-0 z-40">
          <div className="flex items-center">
            <button onClick={() => setIsSidebarOpen(!isSidebarOpen)} className="p-2 hover:bg-slate-50 rounded-lg text-slate-500 me-4">
              <Menu size={20} />
            </button>
            <div className="flex items-center text-sm text-slate-400">
              <span>หน้าแรก</span>
              <ChevronRight size={14} className="mx-2" />
              <span className="text-slate-900 font-medium capitalize">{activeTab.replace('_', ' ')}</span>
            </div>
          </div>

          <div className="flex items-center space-x-4">
            <div className="relative hidden md:block">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <input type="text" placeholder="ค้นหาข้อมูล..." className="bg-slate-50 border-0 rounded-full py-2 ps-10 pe-4 text-sm w-64 focus:ring-2 focus:ring-blue-500/20 transition-all" />
            </div>
            <button className="p-2 text-slate-400 hover:bg-slate-50 rounded-full relative">
              <Bell size={20} />
              <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
            </button>
            <div className="h-8 w-px bg-slate-100 mx-2"></div>
            <div className="flex items-center">
              <div className="text-end me-3 hidden sm:block">
                <p className="text-xs font-bold text-slate-900 leading-none mb-1">{user.name}</p>
                <p className="text-[10px] text-slate-400 leading-none">{user.role.toUpperCase()}</p>
              </div>
              <img src={user.avatar} alt="Avatar" className="w-9 h-9 rounded-xl border-2 border-white shadow-sm" />
            </div>
          </div>
        </header>

        {/* Content Container */}
        <div className="p-6 lg:p-8 flex-1">
          {activeTab === 'dashboard' && (
            <div className="space-y-8">
              {/* Approval Section for Admins */}
              {pendingUsers.length > 0 && (
                <div className="bg-orange-50 border border-orange-200 rounded-3xl p-6 shadow-sm">
                  <div className="flex items-center mb-4">
                    <AlertCircle className="text-orange-500 me-2" />
                    <h5 className="font-bold text-orange-900">มีคำขอสมัครสมาชิกใหม่ ({pendingUsers.length})</h5>
                  </div>
                  <div className="space-y-3">
                    {pendingUsers.map(u => (
                      <div key={u.id} className="bg-white p-4 rounded-2xl flex items-center justify-between shadow-sm border border-orange-100">
                        <div>
                          <p className="font-bold text-slate-900">{u.name}</p>
                          <p className="text-xs text-slate-500">{u.position} • {u.school_name || 'ไม่ระบุโรงเรียน'}</p>
                          <p className="text-[10px] text-slate-400">เลขบัตร: {u.national_id}</p>
                        </div>
                        <button 
                          onClick={() => handleApprove(u.id)}
                          className="px-4 py-2 bg-green-600 text-white rounded-xl text-xs font-bold hover:bg-green-700 transition-all"
                        >
                          อนุมัติการใช้งาน
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Welcome Banner */}
              <div className="bg-blue-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-blue-900/20">
                <div className="relative z-10">
                  <h2 className="text-3xl font-bold mb-2 flex items-center">
                    <LayoutDashboard className="me-3" /> Dashboard {user.role === 'teacher' ? 'ครูผู้สอน' : 'ผู้ดูแลระบบ'}
                  </h2>
                  <p className="text-blue-100">ยินดีต้อนรับกลับมา, {user.name}</p>
                </div>
                <div className="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
              </div>

              {/* Stat Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard title="นักเรียนทั้งหมด" value="91" subtitle="คน" icon={Users} colorClass="bg-cyan-500" />
                <StatCard title="บุคลากร" value="4" subtitle="คน" icon={UserPlus} colorClass="bg-purple-500" />
                <StatCard title="ระดับชั้น" value="6" subtitle="ระดับ" icon={GraduationCap} colorClass="bg-orange-500" />
                <StatCard title="รายวิชา" value="12" subtitle="วิชา" icon={BookMarked} colorClass="bg-amber-400" />
              </div>

              {/* Charts Section Placeholder */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="glass-card">
                  <h5 className="font-bold mb-6 flex items-center">
                    <TrendingUp className="me-2 text-blue-600" size={18} /> กราฟจำนวนนักเรียนแต่ละชั้นเรียน
                  </h5>
                  <div className="h-64 bg-slate-50 rounded-xl flex items-end justify-around p-4">
                    {[40, 70, 45, 90, 65, 85].map((h, i) => (
                      <div key={i} className="w-12 bg-blue-500 rounded-t-lg transition-all hover:bg-blue-600" style={{ height: `${h}%` }}></div>
                    ))}
                  </div>
                  <div className="flex justify-around mt-4 text-[10px] font-bold text-slate-400">
                    <span>ป.1</span><span>ป.2</span><span>ป.3</span><span>ป.4</span><span>ป.5</span><span>ป.6</span>
                  </div>
                </div>
                
                <div className="glass-card">
                  <h5 className="font-bold mb-6 flex items-center text-slate-900">
                    <Award className="me-2 text-purple-600" size={18} /> สรุปผลการเรียนเฉลี่ย
                  </h5>
                  <div className="flex items-center justify-center h-64">
                    <div className="w-48 h-48 rounded-full border-[16px] border-slate-100 border-t-purple-500 flex items-center justify-center">
                      <div className="text-center">
                        <span className="text-4xl font-bold text-slate-900">3.67</span>
                        <p className="text-xs text-slate-400">GPA เฉลี่ย</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'grading' && (
            <div className="glass-card">
              <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                  <h4 className="text-xl font-bold text-slate-900">บันทึกคะแนน ปพ.5</h4>
                  <p className="text-sm text-slate-400">วิชา ภาษาไทย (ท11101) • ชั้นประถมศึกษาปีที่ 1/1</p>
                </div>
                <div className="flex space-x-2">
                  <button className="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-black transition-all flex items-center">
                    <Printer size={16} className="me-2" /> พิมพ์รายงาน
                  </button>
                  <button className="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all">
                    บันทึกทั้งหมด
                  </button>
                </div>
              </div>

              <div className="overflow-x-auto -mx-5 px-5">
                <table className="w-full text-sm text-left border-separate border-spacing-y-2">
                  <thead>
                    <tr className="text-slate-400 uppercase text-[10px] font-bold tracking-wider">
                      <th className="px-4 py-3">เลขที่</th>
                      <th className="px-4 py-3">ชื่อ-นามสกุล</th>
                      <th className="px-4 py-3 text-center">คะแนนเก็บ (50)</th>
                      <th className="px-4 py-3 text-center">กลางปี (20)</th>
                      <th className="px-4 py-3 text-center">ปลายปี (30)</th>
                      <th className="px-4 py-3 text-center">รวม (100)</th>
                      <th className="px-4 py-3 text-center">เกรด</th>
                    </tr>
                  </thead>
                  <tbody>
                    {students.map((s, i) => (
                      <tr key={s.id} className="bg-white hover:bg-slate-50 transition-colors group border border-slate-100">
                        <td className="px-4 py-4 rounded-s-2xl border-y border-s border-slate-100">{i + 1}</td>
                        <td className="px-4 py-4 border-y border-slate-100 font-semibold">{s.name}</td>
                        <td className="px-4 py-4 border-y border-slate-100 text-center">
                          <input type="number" defaultValue={s.k + s.p + s.a} className="w-16 text-center bg-slate-50 border-0 rounded-lg py-1 focus:ring-2 focus:ring-blue-500/20" />
                        </td>
                        <td className="px-4 py-4 border-y border-slate-100 text-center">
                          <input type="number" defaultValue={s.midterm} className="w-16 text-center bg-slate-50 border-0 rounded-lg py-1 focus:ring-2 focus:ring-blue-500/20" />
                        </td>
                        <td className="px-4 py-4 border-y border-slate-100 text-center">
                          <input type="number" defaultValue={s.final} className="w-16 text-center bg-slate-50 border-0 rounded-lg py-1 focus:ring-2 focus:ring-blue-500/20" />
                        </td>
                        <td className="px-4 py-4 border-y border-slate-100 text-center font-bold text-blue-600">
                          {s.k + s.p + s.a + s.midterm + s.final}
                        </td>
                        <td className="px-4 py-4 rounded-e-2xl border-y border-e border-slate-100 text-center">
                          <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full font-bold">4.0</span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {activeTab === 'manage_schools' && (
            <div className="space-y-6">
              <div className="glass-card">
                <h4 className="text-xl font-bold mb-6">เพิ่มโรงเรียนใหม่</h4>
                <form onSubmit={handleAddSchool} className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-2 uppercase">รหัส Smith (8 หลัก)</label>
                    <input 
                      type="text" 
                      maxLength={8}
                      value={newSchool.code}
                      onChange={(e) => setNewSchool({...newSchool, code: e.target.value})}
                      className="w-full bg-slate-50 border-0 rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500/20" 
                      placeholder="เช่น 10203040"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-2 uppercase">ชื่อโรงเรียน</label>
                    <input 
                      type="text" 
                      value={newSchool.name}
                      onChange={(e) => setNewSchool({...newSchool, name: e.target.value})}
                      className="w-full bg-slate-50 border-0 rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500/20" 
                      placeholder="ชื่อโรงเรียน..."
                      required
                    />
                  </div>
                  <div className="flex items-end">
                    <button type="submit" className="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all">
                      เพิ่มข้อมูล
                    </button>
                  </div>
                </form>
              </div>

              <div className="glass-card">
                <h4 className="text-xl font-bold mb-6">รายชื่อโรงเรียนในระบบ</h4>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                        <th className="px-4 py-3 rounded-s-xl">รหัส</th>
                        <th className="px-4 py-3">ชื่อโรงเรียน</th>
                        <th className="px-4 py-3">จังหวัด</th>
                        <th className="px-4 py-3 text-center rounded-e-xl">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {schools.map(s => (
                        <tr key={s.id}>
                          <td className="px-4 py-4 font-mono font-bold text-blue-600">{s.code}</td>
                          <td className="px-4 py-4 font-semibold">{s.name}</td>
                          <td className="px-4 py-4 text-slate-500">{s.province || 'เลย'}</td>
                          <td className="px-4 py-4 text-center">
                            <button className="p-2 text-slate-400 hover:text-blue-600 transition-colors"><Settings size={18} /></button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'manage_students' && (
            <div className="space-y-6">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h4 className="text-xl font-bold">จัดการข้อมูลนักเรียน</h4>
                <button onClick={promoteStudents} className="px-6 py-2 bg-orange-500 text-white rounded-xl font-bold hover:bg-orange-600 transition-all flex items-center">
                  <TrendingUp size={18} className="me-2" /> เลื่อนชั้นนักเรียนทั้งหมด
                </button>
              </div>

              <div className="glass-card">
                <h5 className="font-bold mb-6">เพิ่มนักเรียนใหม่</h5>
                <form onSubmit={handleAddStudent} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-2 uppercase">เลขประจำตัว</label>
                    <input 
                      type="text" 
                      value={newStudent.id}
                      onChange={(e) => setNewStudent({...newStudent, id: e.target.value})}
                      className="w-full bg-slate-50 border-0 rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500/20" 
                      placeholder="เช่น 64001"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-2 uppercase">ชื่อ-นามสกุล</label>
                    <input 
                      type="text" 
                      value={newStudent.name}
                      onChange={(e) => setNewStudent({...newStudent, name: e.target.value})}
                      className="w-full bg-slate-50 border-0 rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500/20" 
                      placeholder="ชื่อ-นามสกุล..."
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-2 uppercase">ระดับชั้น</label>
                    <select 
                      value={newStudent.level}
                      onChange={(e) => setNewStudent({...newStudent, level: e.target.value})}
                      className="w-full bg-slate-50 border-0 rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500/20"
                    >
                      {['ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6'].map(l => <option key={l} value={l}>{l}</option>)}
                    </select>
                  </div>
                  <div className="flex items-end">
                    <button type="submit" className="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all">
                      เพิ่มนักเรียน
                    </button>
                  </div>
                </form>
              </div>

              <div className="glass-card">
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                        <th className="px-4 py-3 rounded-s-xl">รูป</th>
                        <th className="px-4 py-3">เลขประจำตัว</th>
                        <th className="px-4 py-3">ชื่อ-นามสกุล</th>
                        <th className="px-4 py-3">ระดับชั้น</th>
                        <th className="px-4 py-3 text-center rounded-e-xl">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {students.map(s => (
                        <tr key={s.id}>
                          <td className="px-4 py-3">
                            <div className="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400">
                              <User size={20} />
                            </div>
                          </td>
                          <td className="px-4 py-4 font-mono font-bold">{s.id}</td>
                          <td className="px-4 py-4 font-semibold">{s.name}</td>
                          <td className="px-4 py-4">
                            <span className="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-bold">{s.level}</span>
                          </td>
                          <td className="px-4 py-4 text-center">
                            <button className="p-2 text-slate-400 hover:text-blue-600 transition-colors"><Settings size={18} /></button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'manage_teachers' && (
            <div className="glass-card">
              <h4 className="text-xl font-bold mb-6">จัดการข้อมูลบุคลากร</h4>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                      <th className="px-4 py-3 rounded-s-xl">ชื่อ-นามสกุล</th>
                      <th className="px-4 py-3">วิชาที่สอน</th>
                      <th className="px-4 py-3">ครูประจำชั้น</th>
                      <th className="px-4 py-3 text-center rounded-e-xl">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {teachers.map(t => (
                      <tr key={t.id}>
                        <td className="px-4 py-4 font-semibold">{t.name}</td>
                        <td className="px-4 py-4">{t.subject}</td>
                        <td className="px-4 py-4">{t.level}</td>
                        <td className="px-4 py-4 text-center">
                          <button className="p-2 text-slate-400 hover:text-blue-600 transition-colors"><Settings size={18} /></button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

                  {activeTab === 'manage_curriculum' && (
            <div className="space-y-6">
              <div className="glass-card">
                <h4 className="text-xl font-bold mb-6">จัดการหลักสูตรและรายวิชา</h4>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                  <div className="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                    <p className="text-[10px] font-bold text-blue-600 uppercase mb-1">วิชาพื้นฐาน</p>
                    <p className="text-2xl font-bold text-slate-900">8</p>
                  </div>
                  <div className="p-4 bg-purple-50 rounded-2xl border border-purple-100">
                    <p className="text-[10px] font-bold text-purple-600 uppercase mb-1">วิชาเพิ่มเติม</p>
                    <p className="text-2xl font-bold text-slate-900">4</p>
                  </div>
                  <div className="p-4 bg-orange-50 rounded-2xl border border-orange-100">
                    <p className="text-[10px] font-bold text-orange-600 uppercase mb-1">กิจกรรมพัฒนาผู้เรียน</p>
                    <p className="text-2xl font-bold text-slate-900">3</p>
                  </div>
                </div>
                
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                        <th className="px-4 py-3 rounded-s-xl">รหัสวิชา</th>
                        <th className="px-4 py-3">ชื่อวิชา</th>
                        <th className="px-4 py-3">ประเภท</th>
                        <th className="px-4 py-3 text-center">หน่วยกิต</th>
                        <th className="px-4 py-3 text-center rounded-e-xl">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      <tr>
                        <td className="px-4 py-4 font-mono font-bold">ท 11101</td>
                        <td className="px-4 py-4 font-semibold">ภาษาไทย</td>
                        <td className="px-4 py-4"><span className="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">พื้นฐาน</span></td>
                        <td className="px-4 py-4 text-center">1.0</td>
                        <td className="px-4 py-4 text-center">
                          <button className="p-2 text-slate-400 hover:text-blue-600 transition-colors"><Settings size={18} /></button>
                        </td>
                      </tr>
                      <tr>
                        <td className="px-4 py-4 font-mono font-bold">ค 11101</td>
                        <td className="px-4 py-4 font-semibold">คณิตศาสตร์</td>
                        <td className="px-4 py-4"><span className="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">พื้นฐาน</span></td>
                        <td className="px-4 py-4 text-center">1.0</td>
                        <td className="px-4 py-4 text-center">
                          <button className="p-2 text-slate-400 hover:text-blue-600 transition-colors"><Settings size={18} /></button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'attendance' && (
            <div className="glass-card">
              <h4 className="text-xl font-bold mb-6">บันทึกเวลาเรียน</h4>
              <p className="text-slate-500">เช็คชื่อนักเรียนรายวันหรือรายคาบ</p>
              <div className="mt-8 p-12 border-2 border-dashed border-slate-100 rounded-3xl text-center">
                <Calendar size={48} className="mx-auto text-slate-200 mb-4" />
                <p className="text-slate-400 font-medium">กำลังพัฒนาระบบเช็คชื่อ...</p>
              </div>
            </div>
          )}

          {activeTab === 'characteristics' && (
            <div className="glass-card">
              <h4 className="text-xl font-bold mb-6">ประเมินคุณลักษณะอันพึงประสงค์</h4>
              <p className="text-slate-500">ประเมิน 8 ข้อหลักตามหลักสูตรแกนกลาง</p>
              <div className="mt-8 p-12 border-2 border-dashed border-slate-100 rounded-3xl text-center">
                <CheckCircle size={48} className="mx-auto text-slate-200 mb-4" />
                <p className="text-slate-400 font-medium">กำลังพัฒนาระบบประเมินคุณลักษณะ...</p>
              </div>
            </div>
          )}

          {activeTab === 'analytical' && (
            <div className="glass-card">
              <h4 className="text-xl font-bold mb-6">ประเมินการอ่าน คิดวิเคราะห์ และเขียน</h4>
              <p className="text-slate-500">ประเมินทักษะการสื่อสารและการคิดของนักเรียน</p>
              <div className="mt-8 p-12 border-2 border-dashed border-slate-100 rounded-3xl text-center">
                <ClipboardList size={48} className="mx-auto text-slate-200 mb-4" />
                <p className="text-slate-400 font-medium">กำลังพัฒนาระบบประเมินการอ่าน...</p>
              </div>
            </div>
          )}

          {activeTab === 'papor6' && (
            <div className="space-y-6">
              <div className="glass-card bg-blue-600 text-white border-none">
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <div className="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center me-4">
                      <GraduationCap size={32} />
                    </div>
                    <div>
                      <h4 className="text-xl font-bold">ผลการเรียน (ปพ.6)</h4>
                      <p className="text-blue-100 text-sm">รายงานผลการพัฒนาคุณภาพผู้เรียนรายบุคคล</p>
                    </div>
                  </div>
                  <button className="px-6 py-2 bg-white text-blue-600 rounded-xl font-bold hover:bg-blue-50 transition-all">
                    พิมพ์ ปพ.6
                  </button>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="glass-card text-center">
                  <p className="text-xs text-slate-400 mb-1">ระดับชั้น</p>
                  <p className="font-bold text-blue-600">ประถมศึกษาปีที่ 1</p>
                </div>
                <div className="glass-card text-center">
                  <p className="text-xs text-slate-400 mb-1">GPA</p>
                  <p className="text-2xl font-bold text-green-500">3.67</p>
                </div>
                <div className="glass-card text-center">
                  <p className="text-xs text-slate-400 mb-1">หน่วยกิต (พื้นฐาน)</p>
                  <p className="font-bold">1.5 / 1.5</p>
                </div>
                <div className="glass-card text-center">
                  <p className="text-xs text-slate-400 mb-1">หน่วยกิต (เพิ่มเติม)</p>
                  <p className="font-bold">0 / 0</p>
                </div>
              </div>

              <div className="glass-card">
                <h5 className="font-bold mb-6">ตารางผลการเรียน</h5>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                        <th className="px-4 py-3 rounded-s-xl">รายวิชา</th>
                        <th className="px-4 py-3">รหัสวิชา</th>
                        <th className="px-4 py-3">ประเภท</th>
                        <th className="px-4 py-3 text-center">หน่วยกิต</th>
                        <th className="px-4 py-3 text-center">เทอม 1</th>
                        <th className="px-4 py-3 text-center">เทอม 2</th>
                        <th className="px-4 py-3 text-center">รวม</th>
                        <th className="px-4 py-3 text-center rounded-e-xl">เกรด</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      <tr>
                        <td className="px-4 py-4 font-semibold">การงานอาชีพ</td>
                        <td className="px-4 py-4 text-slate-400">ง 11101</td>
                        <td className="px-4 py-4"><span className="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">พื้นฐาน</span></td>
                        <td className="px-4 py-4 text-center">0.5</td>
                        <td className="px-4 py-4 text-center">45</td>
                        <td className="px-4 py-4 text-center">40</td>
                        <td className="px-4 py-4 text-center font-bold">85</td>
                        <td className="px-4 py-4 text-center font-bold text-green-500">4</td>
                      </tr>
                      <tr>
                        <td className="px-4 py-4 font-semibold">คณิตศาสตร์พื้นฐาน</td>
                        <td className="px-4 py-4 text-slate-400">ค 11101</td>
                        <td className="px-4 py-4"><span className="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">พื้นฐาน</span></td>
                        <td className="px-4 py-4 text-center">1.0</td>
                        <td className="px-4 py-4 text-center">30</td>
                        <td className="px-4 py-4 text-center">45</td>
                        <td className="px-4 py-4 text-center font-bold">75</td>
                        <td className="px-4 py-4 text-center font-bold text-green-500">3.5</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <footer className="h-16 bg-white border-t border-slate-100 flex items-center justify-center px-6 text-slate-400 text-xs">
          <p>© 2024 ระบบบริหารจัดการสถานศึกษา • พัฒนาโดย ทีมเทคโนโลยีสารสนเทศ</p>
        </footer>
      </main>
    </div>
  );
}
