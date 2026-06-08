export interface User {
  id: number;
  name: string;
  email: string;
  username?: string;
  role: 'admin' | 'headmaster' | 'teacher' | 'bursar' | 'storekeeper' | 'parent' | 'student' | 'user';
  phone?: string;
  must_change_password?: boolean;
}

export interface Staff {
  id: number;
  user_id: number;
  staff_id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  phone?: string;
  department?: string;
  position?: string;
  roles: string[];
  qualifications?: string;
  employment_date?: string;
  is_active: boolean;
}

export interface SchoolClass {
  id: number;
  class_name: string;
  stream?: string;
  display_name: string;
  academic_year: string;
  capacity?: number;
  class_teacher_id?: number;
  classTeacher?: Staff;
}

export interface Subject {
  id: number;
  subject_name: string;
  subject_code: string;
  description?: string;
}

export interface Guardian {
  id: number;
  student_id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email?: string;
  phone: string;
  relationship: string;
  address?: string;
  city?: string;
  country?: string;
  occupation?: string;
  is_primary_contact: boolean;
}

export interface Student {
  id: number;
  user_id?: number;
  admission_number: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email?: string;
  date_of_birth?: string;
  gender?: 'male' | 'female' | 'other';
  class_id?: number;
  schoolClass?: SchoolClass;
  admission_date: string;
  status: 'active' | 'inactive' | 'transferred' | 'graduated' | 'suspended';
  blood_type?: string;
  allergies?: string;
  medical_conditions?: string;
  guardians?: Guardian[];
  fees?: StudentFee[];
  academicProgress?: AcademicProgress[];
  attendance?: Attendance[];
  behaviourRecords?: BehaviourRecord[];
  teacherComments?: TeacherComment[];
}

export interface StudentFee {
  id: number;
  student_id: number;
  student?: Student;
  academic_year: string;
  term: 'term1' | 'term2' | 'term3';
  amount: number;
  amount_paid: number;
  balance: number;
  due_date: string;
  status: 'pending' | 'partial' | 'paid' | 'overdue';
}

export interface Payment {
  id: number;
  payment_number: string;
  student_id: number;
  student?: Student;
  student_fee_id: number;
  amount: number;
  payment_method: 'cash' | 'bank_transfer' | 'check' | 'online';
  payment_date: string;
  receipt_number?: string;
  notes?: string;
  recorded_by: number;
  recordedBy?: User;
}

export interface FeeStructure {
  id: number;
  academic_year: string;
  class_name: string;
  term: 'term1' | 'term2' | 'term3';
  amount: number;
  due_date: string;
  description?: string;
}

export interface AcademicProgress {
  id: number;
  student_id: number;
  class_id: number;
  subject_id: number;
  subject?: Subject;
  academic_year: string;
  term: 'term1' | 'term2' | 'term3';
  assessment_type: 'weekly_test' | 'monthly_test' | 'assignment' | 'exam' | 'project';
  marks?: number;
  total_marks?: number;
  percentage?: number;
  grade?: string;
}

export interface Attendance {
  id: number;
  student_id: number;
  class_id: number;
  date: string;
  status: 'present' | 'absent' | 'late' | 'excused' | 'sick' | 'early_departure';
  remarks?: string;
}

export interface BehaviourRecord {
  id: number;
  student_id: number;
  student?: Student;
  class_id: number;
  issue_date: string;
  issue_type: string;
  severity: 'minor' | 'moderate' | 'severe';
  description: string;
  action?: string;
  headmaster_review?: string;
  reviewed_at?: string;
}

export interface TeacherComment {
  id: number;
  student_id: number;
  class_id: number;
  teacher_id: number;
  teacher?: Staff;
  academic_year: string;
  term: string;
  progress?: string;
  participation?: string;
  homework?: string;
  behaviour?: string;
  areas_for_improvement?: string;
  strengths?: string;
}

export interface Announcement {
  id: number;
  title: string;
  content: string;
  audience: 'all' | 'parents' | 'students' | 'staff' | 'specific_class';
  is_active: boolean;
  created_at: string;
  createdBy?: User;
}

export interface Application {
  id: number;
  application_number: string;
  first_name: string;
  middle_name?: string;
  last_name: string;
  full_name: string;
  email: string;
  phone: string;
  date_of_birth: string;
  id_number?: string;
  student_address?: string;
  previous_school?: string;
  former_grade?: string;
  reason_for_joining?: string;
  doc_student_id_path?: string;
  doc_results_path?: string;
  doc_parent_id_path?: string;
  doc_transfer_letter_path?: string;
  guardian_name: string;
  guardian_email: string;
  guardian_phone: string;
  guardian2_name?: string;
  guardian2_email?: string;
  guardian2_phone?: string;
  guardian3_name?: string;
  guardian3_email?: string;
  guardian3_phone?: string;
  intended_class: string;
  academic_year: string;
  status: 'pending' | 'approved' | 'rejected' | 'enrolled';
  rejection_reason?: string;
  created_at: string;
}

export interface TeacherSubject {
  id: number;
  staff_id: number;
  class_id: number;
  subject_id: number;
  academic_year: string;
  schoolClass?: SchoolClass;
  subject?: Subject;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
