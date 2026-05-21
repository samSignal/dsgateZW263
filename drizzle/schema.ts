import {
  int,
  mysqlEnum,
  mysqlTable,
  text,
  timestamp,
  varchar,
  decimal,
  date,
  boolean,
  json,
  uniqueIndex,
  index,
  foreignKey,
} from "drizzle-orm/mysql-core";

/**
 * Core user table backing auth flow.
 * Extended with role field for role-based access control.
 */
export const users = mysqlTable(
  "users",
  {
    id: int("id").autoincrement().primaryKey(),
    openId: varchar("openId", { length: 64 }).notNull().unique(),
    name: text("name"),
    email: varchar("email", { length: 320 }).unique(),
    phone: varchar("phone", { length: 20 }),
    loginMethod: varchar("loginMethod", { length: 64 }),
    role: mysqlEnum("role", ["admin", "headmaster", "teacher", "bursar", "parent", "student", "user"]).default("user").notNull(),
    isActive: boolean("isActive").default(true).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
    lastSignedIn: timestamp("lastSignedIn").defaultNow().notNull(),
  },
  (table) => ({
    emailIdx: index("email_idx").on(table.email),
    roleIdx: index("role_idx").on(table.role),
  })
);

export type User = typeof users.$inferSelect;
export type InsertUser = typeof users.$inferInsert;

/**
 * Staff members - Teachers, Bursars, Admin staff
 */
export const staff = mysqlTable(
  "staff",
  {
    id: int("id").autoincrement().primaryKey(),
    userId: int("userId").notNull(),
    staffId: varchar("staffId", { length: 50 }).notNull().unique(),
    firstName: varchar("firstName", { length: 100 }).notNull(),
    lastName: varchar("lastName", { length: 100 }).notNull(),
    email: varchar("email", { length: 320 }).notNull(),
    phone: varchar("phone", { length: 20 }),
    department: varchar("department", { length: 100 }),
    position: varchar("position", { length: 100 }),
    roles: json("roles").$type<string[]>().default([]).notNull(), // Multiple roles per staff
    qualifications: text("qualifications"),
    employmentDate: date("employmentDate"),
    isActive: boolean("isActive").default(true).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    userIdIdx: index("staff_userId_idx").on(table.userId),
    staffIdIdx: index("staff_staffId_idx").on(table.staffId),
  })
);

export type Staff = typeof staff.$inferSelect;
export type InsertStaff = typeof staff.$inferInsert;

/**
 * Classes/Forms - School classes and streams
 */
export const classes = mysqlTable(
  "classes",
  {
    id: int("id").autoincrement().primaryKey(),
    className: varchar("className", { length: 100 }).notNull(),
    stream: varchar("stream", { length: 50 }),
    classTeacherId: int("classTeacherId"),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    capacity: int("capacity"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    classNameIdx: index("class_className_idx").on(table.className),
    academicYearIdx: index("class_academicYear_idx").on(table.academicYear),
  })
);

export type Class = typeof classes.$inferSelect;
export type InsertClass = typeof classes.$inferInsert;

/**
 * Subjects - School subjects
 */
export const subjects = mysqlTable(
  "subjects",
  {
    id: int("id").autoincrement().primaryKey(),
    subjectName: varchar("subjectName", { length: 100 }).notNull().unique(),
    subjectCode: varchar("subjectCode", { length: 20 }).notNull().unique(),
    description: text("description"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  }
);

export type Subject = typeof subjects.$inferSelect;
export type InsertSubject = typeof subjects.$inferInsert;

/**
 * Teacher-Subject allocation
 */
export const teacherSubjects = mysqlTable(
  "teacher_subjects",
  {
    id: int("id").autoincrement().primaryKey(),
    staffId: int("staffId").notNull(),
    classId: int("classId").notNull(),
    subjectId: int("subjectId").notNull(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    staffIdIdx: index("ts_staffId_idx").on(table.staffId),
    classIdIdx: index("ts_classId_idx").on(table.classId),
    subjectIdIdx: index("ts_subjectId_idx").on(table.subjectId),
  })
);

export type TeacherSubject = typeof teacherSubjects.$inferSelect;
export type InsertTeacherSubject = typeof teacherSubjects.$inferInsert;

/**
 * Students - Core student records
 */
export const students = mysqlTable(
  "students",
  {
    id: int("id").autoincrement().primaryKey(),
    userId: int("userId"),
    admissionNumber: varchar("admissionNumber", { length: 50 }).notNull().unique(),
    firstName: varchar("firstName", { length: 100 }).notNull(),
    lastName: varchar("lastName", { length: 100 }).notNull(),
    email: varchar("email", { length: 320 }),
    dateOfBirth: date("dateOfBirth"),
    gender: mysqlEnum("gender", ["male", "female", "other"]),
    classId: int("classId"),
    admissionDate: date("admissionDate").notNull(),
    status: mysqlEnum("status", ["active", "inactive", "transferred", "graduated", "suspended"]).default("active").notNull(),
    bloodType: varchar("bloodType", { length: 10 }),
    allergies: text("allergies"),
    medicalConditions: text("medicalConditions"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    admissionNumberIdx: index("student_admissionNumber_idx").on(table.admissionNumber),
    classIdIdx: index("student_classId_idx").on(table.classId),
    statusIdx: index("student_status_idx").on(table.status),
  })
);

export type Student = typeof students.$inferSelect;
export type InsertStudent = typeof students.$inferInsert;

/**
 * Guardians/Parents
 */
export const guardians = mysqlTable(
  "guardians",
  {
    id: int("id").autoincrement().primaryKey(),
    userId: int("userId"),
    studentId: int("studentId").notNull(),
    firstName: varchar("firstName", { length: 100 }).notNull(),
    lastName: varchar("lastName", { length: 100 }).notNull(),
    email: varchar("email", { length: 320 }),
    phone: varchar("phone", { length: 20 }).notNull(),
    relationship: varchar("relationship", { length: 50 }).notNull(), // Parent, Guardian, etc.
    address: text("address"),
    city: varchar("city", { length: 100 }),
    country: varchar("country", { length: 100 }),
    occupation: varchar("occupation", { length: 100 }),
    isPrimaryContact: boolean("isPrimaryContact").default(false).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("guardian_studentId_idx").on(table.studentId),
    userIdIdx: index("guardian_userId_idx").on(table.userId),
  })
);

export type Guardian = typeof guardians.$inferSelect;
export type InsertGuardian = typeof guardians.$inferInsert;

/**
 * Student Documents - Store file references
 */
export const studentDocuments = mysqlTable(
  "student_documents",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    documentType: varchar("documentType", { length: 100 }).notNull(), // Birth certificate, admission letter, etc.
    fileName: varchar("fileName", { length: 255 }).notNull(),
    fileUrl: text("fileUrl").notNull(),
    fileKey: text("fileKey").notNull(),
    uploadedAt: timestamp("uploadedAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("doc_studentId_idx").on(table.studentId),
  })
);

export type StudentDocument = typeof studentDocuments.$inferSelect;
export type InsertStudentDocument = typeof studentDocuments.$inferInsert;

/**
 * Applications/Admissions
 */
export const applications = mysqlTable(
  "applications",
  {
    id: int("id").autoincrement().primaryKey(),
    applicationNumber: varchar("applicationNumber", { length: 50 }).notNull().unique(),
    firstName: varchar("firstName", { length: 100 }).notNull(),
    lastName: varchar("lastName", { length: 100 }).notNull(),
    email: varchar("email", { length: 320 }).notNull(),
    phone: varchar("phone", { length: 20 }).notNull(),
    dateOfBirth: date("dateOfBirth").notNull(),
    guardianName: varchar("guardianName", { length: 100 }).notNull(),
    guardianEmail: varchar("guardianEmail", { length: 320 }).notNull(),
    guardianPhone: varchar("guardianPhone", { length: 20 }).notNull(),
    intendedClass: varchar("intendedClass", { length: 100 }).notNull(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    status: mysqlEnum("status", ["pending", "approved", "rejected", "enrolled"]).default("pending").notNull(),
    rejectionReason: text("rejectionReason"),
    processedBy: int("processedBy"),
    processedAt: timestamp("processedAt"),
    enrolledStudentId: int("enrolledStudentId"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    statusIdx: index("app_status_idx").on(table.status),
    academicYearIdx: index("app_academicYear_idx").on(table.academicYear),
  })
);

export type Application = typeof applications.$inferSelect;
export type InsertApplication = typeof applications.$inferInsert;

/**
 * School Fees Configuration
 */
export const feeStructures = mysqlTable(
  "fee_structures",
  {
    id: int("id").autoincrement().primaryKey(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    className: varchar("className", { length: 100 }).notNull(),
    term: mysqlEnum("term", ["term1", "term2", "term3"]).notNull(),
    amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
    dueDate: date("dueDate").notNull(),
    description: text("description"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    academicYearIdx: index("fs_academicYear_idx").on(table.academicYear),
  })
);

export type FeeStructure = typeof feeStructures.$inferSelect;
export type InsertFeeStructure = typeof feeStructures.$inferInsert;

/**
 * Student Fees - Individual student fee records
 */
export const studentFees = mysqlTable(
  "student_fees",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    term: mysqlEnum("term", ["term1", "term2", "term3"]).notNull(),
    amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
    amountPaid: decimal("amountPaid", { precision: 12, scale: 2 }).default(0).notNull(),
    balance: decimal("balance", { precision: 12, scale: 2 }).notNull(),
    dueDate: date("dueDate").notNull(),
    status: mysqlEnum("status", ["pending", "partial", "paid", "overdue"]).default("pending").notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("sf_studentId_idx").on(table.studentId),
    academicYearIdx: index("sf_academicYear_idx").on(table.academicYear),
    statusIdx: index("sf_status_idx").on(table.status),
  })
);

export type StudentFee = typeof studentFees.$inferSelect;
export type InsertStudentFee = typeof studentFees.$inferInsert;

/**
 * Payments - Payment records
 */
export const payments = mysqlTable(
  "payments",
  {
    id: int("id").autoincrement().primaryKey(),
    paymentNumber: varchar("paymentNumber", { length: 50 }).notNull().unique(),
    studentId: int("studentId").notNull(),
    studentFeeId: int("studentFeeId").notNull(),
    amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
    paymentMethod: mysqlEnum("paymentMethod", ["cash", "bank_transfer", "check", "online"]).notNull(),
    paymentDate: date("paymentDate").notNull(),
    receiptNumber: varchar("receiptNumber", { length: 50 }).unique(),
    notes: text("notes"),
    recordedBy: int("recordedBy").notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("payment_studentId_idx").on(table.studentId),
    paymentDateIdx: index("payment_paymentDate_idx").on(table.paymentDate),
  })
);

export type Payment = typeof payments.$inferSelect;
export type InsertPayment = typeof payments.$inferInsert;

/**
 * Academic Progress - Marks and assessments
 */
export const academicProgress = mysqlTable(
  "academic_progress",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    classId: int("classId").notNull(),
    subjectId: int("subjectId").notNull(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    term: mysqlEnum("term", ["term1", "term2", "term3"]).notNull(),
    assessmentType: mysqlEnum("assessmentType", ["weekly_test", "monthly_test", "assignment", "exam", "project"]).notNull(),
    marks: decimal("marks", { precision: 5, scale: 2 }),
    totalMarks: decimal("totalMarks", { precision: 5, scale: 2 }),
    percentage: decimal("percentage", { precision: 5, scale: 2 }),
    grade: varchar("grade", { length: 5 }),
    recordedBy: int("recordedBy").notNull(),
    recordedAt: timestamp("recordedAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("ap_studentId_idx").on(table.studentId),
    classIdIdx: index("ap_classId_idx").on(table.classId),
    subjectIdIdx: index("ap_subjectId_idx").on(table.subjectId),
    termIdx: index("ap_term_idx").on(table.term),
  })
);

export type AcademicProgress = typeof academicProgress.$inferSelect;
export type InsertAcademicProgress = typeof academicProgress.$inferInsert;

/**
 * Teacher Comments
 */
export const teacherComments = mysqlTable(
  "teacher_comments",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    classId: int("classId").notNull(),
    teacherId: int("teacherId").notNull(),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    term: mysqlEnum("term", ["term1", "term2", "term3"]).notNull(),
    progress: text("progress"),
    participation: text("participation"),
    homework: text("homework"),
    behaviour: text("behaviour"),
    areasForImprovement: text("areasForImprovement"),
    strengths: text("strengths"),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("tc_studentId_idx").on(table.studentId),
    classIdIdx: index("tc_classId_idx").on(table.classId),
  })
);

export type TeacherComment = typeof teacherComments.$inferSelect;
export type InsertTeacherComment = typeof teacherComments.$inferInsert;

/**
 * Attendance
 */
export const attendance = mysqlTable(
  "attendance",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    classId: int("classId").notNull(),
    date: date("date").notNull(),
    status: mysqlEnum("status", ["present", "absent", "late", "excused", "sick", "early_departure"]).notNull(),
    remarks: text("remarks"),
    recordedBy: int("recordedBy").notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("att_studentId_idx").on(table.studentId),
    classIdIdx: index("att_classId_idx").on(table.classId),
    dateIdx: index("att_date_idx").on(table.date),
  })
);

export type Attendance = typeof attendance.$inferSelect;
export type InsertAttendance = typeof attendance.$inferInsert;

/**
 * Behaviour and Discipline
 */
export const behaviourRecords = mysqlTable(
  "behaviour_records",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    classId: int("classId").notNull(),
    issueDate: date("issueDate").notNull(),
    issueType: varchar("issueType", { length: 100 }).notNull(), // Fighting, late, disrespect, etc.
    severity: mysqlEnum("severity", ["minor", "moderate", "severe"]).notNull(),
    description: text("description").notNull(),
    action: varchar("action", { length: 100 }), // Warning, suspension, etc.
    parentMeetingScheduled: boolean("parentMeetingScheduled").default(false).notNull(),
    parentMeetingDate: date("parentMeetingDate"),
    parentMeetingNotes: text("parentMeetingNotes"),
    headmasterReview: text("headmasterReview"),
    reviewedAt: timestamp("reviewedAt"),
    recordedBy: int("recordedBy").notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("br_studentId_idx").on(table.studentId),
    issueDateIdx: index("br_issueDate_idx").on(table.issueDate),
  })
);

export type BehaviourRecord = typeof behaviourRecords.$inferSelect;
export type InsertBehaviourRecord = typeof behaviourRecords.$inferInsert;

/**
 * Student Purchases
 */
export const studentPurchases = mysqlTable(
  "student_purchases",
  {
    id: int("id").autoincrement().primaryKey(),
    studentId: int("studentId").notNull(),
    purchaseDate: date("purchaseDate").notNull(),
    itemType: varchar("itemType", { length: 100 }).notNull(), // Uniform, stationery, books, sportswear, trips
    description: varchar("description", { length: 255 }).notNull(),
    quantity: int("quantity").default(1).notNull(),
    unitPrice: decimal("unitPrice", { precision: 10, scale: 2 }).notNull(),
    totalPrice: decimal("totalPrice", { precision: 10, scale: 2 }).notNull(),
    recordedBy: int("recordedBy").notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    studentIdIdx: index("sp_studentId_idx").on(table.studentId),
    purchaseDateIdx: index("sp_purchaseDate_idx").on(table.purchaseDate),
  })
);

export type StudentPurchase = typeof studentPurchases.$inferSelect;
export type InsertStudentPurchase = typeof studentPurchases.$inferInsert;

/**
 * Notifications
 */
export const notifications = mysqlTable(
  "notifications",
  {
    id: int("id").autoincrement().primaryKey(),
    userId: int("userId").notNull(),
    type: mysqlEnum("type", ["fee_reminder", "fee_overdue", "attendance_alert", "behaviour_warning", "academic_progress", "announcement"]).notNull(),
    title: varchar("title", { length: 255 }).notNull(),
    message: text("message").notNull(),
    relatedStudentId: int("relatedStudentId"),
    relatedRecordId: int("relatedRecordId"),
    isRead: boolean("isRead").default(false).notNull(),
    emailSent: boolean("emailSent").default(false).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    userIdIdx: index("notif_userId_idx").on(table.userId),
    typeIdx: index("notif_type_idx").on(table.type),
  })
);

export type Notification = typeof notifications.$inferSelect;
export type InsertNotification = typeof notifications.$inferInsert;

/**
 * School Announcements/Notices
 */
export const announcements = mysqlTable(
  "announcements",
  {
    id: int("id").autoincrement().primaryKey(),
    title: varchar("title", { length: 255 }).notNull(),
    content: text("content").notNull(),
    audience: mysqlEnum("audience", ["all", "parents", "students", "staff", "specific_class"]).default("all").notNull(),
    targetClassId: int("targetClassId"),
    createdBy: int("createdBy").notNull(),
    isActive: boolean("isActive").default(true).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
    updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  },
  (table) => ({
    audienceIdx: index("ann_audience_idx").on(table.audience),
  })
);

export type Announcement = typeof announcements.$inferSelect;
export type InsertAnnouncement = typeof announcements.$inferInsert;

/**
 * Reports - Store generated reports
 */
export const reports = mysqlTable(
  "reports",
  {
    id: int("id").autoincrement().primaryKey(),
    reportName: varchar("reportName", { length: 255 }).notNull(),
    reportType: mysqlEnum("reportType", ["fees", "academics", "attendance", "behaviour", "purchases", "student_record"]).notNull(),
    format: mysqlEnum("format", ["pdf", "excel"]).notNull(),
    fileUrl: text("fileUrl").notNull(),
    fileKey: text("fileKey").notNull(),
    generatedBy: int("generatedBy").notNull(),
    filters: json("filters").$type<Record<string, unknown>>().notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    reportTypeIdx: index("rep_reportType_idx").on(table.reportType),
  })
);

export type Report = typeof reports.$inferSelect;
export type InsertReport = typeof reports.$inferInsert;

/**
 * Timetable/Schedule
 */
export const timetables = mysqlTable(
  "timetables",
  {
    id: int("id").autoincrement().primaryKey(),
    classId: int("classId").notNull(),
    dayOfWeek: mysqlEnum("dayOfWeek", ["monday", "tuesday", "wednesday", "thursday", "friday"]).notNull(),
    periodNumber: int("periodNumber").notNull(),
    subjectId: int("subjectId").notNull(),
    teacherId: int("teacherId").notNull(),
    startTime: varchar("startTime", { length: 10 }).notNull(), // HH:MM format
    endTime: varchar("endTime", { length: 10 }).notNull(),
    room: varchar("room", { length: 50 }),
    academicYear: varchar("academicYear", { length: 20 }).notNull(),
    createdAt: timestamp("createdAt").defaultNow().notNull(),
  },
  (table) => ({
    classIdIdx: index("tt_classId_idx").on(table.classId),
    dayIdx: index("tt_dayOfWeek_idx").on(table.dayOfWeek),
    periodIdx: index("tt_periodNumber_idx").on(table.periodNumber),
  })
);

export type Timetable = typeof timetables.$inferSelect;
export type InsertTimetable = typeof timetables.$inferInsert;
