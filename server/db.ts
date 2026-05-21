import { eq, and, desc, asc, like, gte, lte, inArray } from "drizzle-orm";
import { drizzle } from "drizzle-orm/mysql2";
import {
  InsertUser,
  users,
  staff,
  students,
  guardians,
  classes,
  subjects,
  applications,
  studentFees,
  payments,
  academicProgress,
  attendance,
  behaviourRecords,
  studentPurchases,
  notifications,
  announcements,
  reports,
  timetables,
  teacherSubjects,
  studentDocuments,
  feeStructures,
  teacherComments,
} from "../drizzle/schema";
import { ENV } from "./_core/env";

let _db: ReturnType<typeof drizzle> | null = null;

export async function getDb() {
  if (!_db && process.env.DATABASE_URL) {
    try {
      _db = drizzle(process.env.DATABASE_URL);
    } catch (error) {
      console.warn("[Database] Failed to connect:", error);
      _db = null;
    }
  }
  return _db;
}

export async function upsertUser(user: InsertUser): Promise<void> {
  if (!user.openId) {
    throw new Error("User openId is required for upsert");
  }

  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot upsert user: database not available");
    return;
  }

  try {
    const values: InsertUser = {
      openId: user.openId,
    };
    const updateSet: Record<string, unknown> = {};

    const textFields = ["name", "email", "loginMethod", "phone"] as const;
    type TextField = (typeof textFields)[number];

    const assignNullable = (field: TextField) => {
      const value = user[field];
      if (value === undefined) return;
      const normalized = value ?? null;
      values[field] = normalized;
      updateSet[field] = normalized;
    };

    textFields.forEach(assignNullable);

    if (user.lastSignedIn !== undefined) {
      values.lastSignedIn = user.lastSignedIn;
      updateSet.lastSignedIn = user.lastSignedIn;
    }
    if (user.role !== undefined) {
      values.role = user.role;
      updateSet.role = user.role;
    } else if (user.openId === ENV.ownerOpenId) {
      values.role = "admin";
      updateSet.role = "admin";
    }

    if (!values.lastSignedIn) {
      values.lastSignedIn = new Date();
    }

    if (Object.keys(updateSet).length === 0) {
      updateSet.lastSignedIn = new Date();
    }

    await db.insert(users).values(values).onDuplicateKeyUpdate({
      set: updateSet,
    });
  } catch (error) {
    console.error("[Database] Failed to upsert user:", error);
    throw error;
  }
}

export async function getUserByOpenId(openId: string) {
  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot get user: database not available");
    return undefined;
  }

  const result = await db
    .select()
    .from(users)
    .where(eq(users.openId, openId))
    .limit(1);

  return result.length > 0 ? result[0] : undefined;
}

// Student Management Queries
export async function getStudentById(id: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(students).where(eq(students.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getStudentByAdmissionNumber(admissionNumber: string) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db
    .select()
    .from(students)
    .where(eq(students.admissionNumber, admissionNumber))
    .limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getStudentsByClass(classId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(students).where(eq(students.classId, classId));
}

export async function getGuardiansByStudent(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(guardians).where(eq(guardians.studentId, studentId));
}

// Staff Management Queries
export async function getStaffByUserId(userId: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(staff).where(eq(staff.userId, userId)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getStaffById(id: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(staff).where(eq(staff.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getAllStaff() {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(staff).where(eq(staff.isActive, true));
}

// Finance Queries
export async function getStudentFeesByStudent(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(studentFees).where(eq(studentFees.studentId, studentId));
}

export async function getPaymentsByStudent(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(payments).where(eq(payments.studentId, studentId));
}

export async function getTotalFeesCollected(academicYear: string) {
  const db = await getDb();
  if (!db) return 0;
  const result = await db
    .select()
    .from(payments)
    .where(eq(payments.createdAt, new Date(academicYear)));
  return result.reduce((sum, p) => sum + Number(p.amount), 0);
}

// Academic Queries
export async function getAcademicProgressByStudent(studentId: number, academicYear: string) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(academicProgress)
    .where(
      and(eq(academicProgress.studentId, studentId), eq(academicProgress.academicYear, academicYear))
    );
}

export async function getTeacherCommentsByStudent(studentId: number, academicYear: string) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(teacherComments)
    .where(
      and(eq(teacherComments.studentId, studentId), eq(teacherComments.academicYear, academicYear))
    );
}

// Attendance Queries
export async function getAttendanceByStudent(studentId: number, fromDate: Date, toDate: Date) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(attendance)
    .where(
      and(
        eq(attendance.studentId, studentId),
        gte(attendance.date, fromDate),
        lte(attendance.date, toDate)
      )
    );
}

export async function getAttendanceByClass(classId: number, date: Date) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(attendance)
    .where(and(eq(attendance.classId, classId), eq(attendance.date, date)));
}

// Behaviour Queries
export async function getBehaviourRecordsByStudent(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(behaviourRecords)
    .where(eq(behaviourRecords.studentId, studentId))
    .orderBy(desc(behaviourRecords.issueDate));
}

// Notifications Queries
export async function getNotificationsByUser(userId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(notifications)
    .where(eq(notifications.userId, userId))
    .orderBy(desc(notifications.createdAt));
}

export async function getUnreadNotifications(userId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(notifications)
    .where(and(eq(notifications.userId, userId), eq(notifications.isRead, false)))
    .orderBy(desc(notifications.createdAt));
}

// Announcements Queries
export async function getActiveAnnouncements() {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(announcements)
    .where(eq(announcements.isActive, true))
    .orderBy(desc(announcements.createdAt));
}

export async function getAnnouncementsByAudience(audience: string) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(announcements)
    .where(and(eq(announcements.isActive, true), eq(announcements.audience, audience as "all" | "parents" | "students" | "staff" | "specific_class")))
    .orderBy(desc(announcements.createdAt));
}

// Class Queries
export async function getClassById(id: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(classes).where(eq(classes.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getClassesByAcademicYear(academicYear: string) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(classes).where(eq(classes.academicYear, academicYear));
}

// Subject Queries
export async function getSubjectById(id: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(subjects).where(eq(subjects.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getAllSubjects() {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(subjects);
}

// Timetable Queries
export async function getTimetableByClass(classId: number, academicYear: string) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(timetables)
    .where(and(eq(timetables.classId, classId), eq(timetables.academicYear, academicYear)))
    .orderBy(asc(timetables.dayOfWeek), asc(timetables.periodNumber));
}

// Application Queries
export async function getApplicationById(id: number) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db.select().from(applications).where(eq(applications.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function getPendingApplications() {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(applications)
    .where(eq(applications.status, "pending"))
    .orderBy(asc(applications.createdAt));
}

// Student Purchases Queries
export async function getStudentPurchases(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(studentPurchases)
    .where(eq(studentPurchases.studentId, studentId))
    .orderBy(desc(studentPurchases.purchaseDate));
}

// Reports Queries
export async function getReportsByType(reportType: string) {
  const db = await getDb();
  if (!db) return [];
  return await db
    .select()
    .from(reports)
    .where(eq(reports.reportType, reportType as "fees" | "academics" | "attendance" | "behaviour" | "purchases" | "student_record"))
    .orderBy(desc(reports.createdAt));
}

// Student Documents Queries
export async function getStudentDocuments(studentId: number) {
  const db = await getDb();
  if (!db) return [];
  return await db.select().from(studentDocuments).where(eq(studentDocuments.studentId, studentId));
}

// Fee Structure Queries
export async function getFeeStructure(academicYear: string, className: string, term: string) {
  const db = await getDb();
  if (!db) return undefined;
  const result = await db
    .select()
    .from(feeStructures)
    .where(
      and(
        eq(feeStructures.academicYear, academicYear),
        eq(feeStructures.className, className),
        eq(feeStructures.term, term as "term1" | "term2" | "term3")
      )
    )
    .limit(1);
  return result.length > 0 ? result[0] : undefined;
}
