import { COOKIE_NAME } from "@shared/const";
import { getSessionCookieOptions } from "./_core/cookies";
import { systemRouter } from "./_core/systemRouter";
import { protectedProcedure, publicProcedure, router } from "./_core/trpc";
import { z } from "zod";
import { TRPCError } from "@trpc/server";
import * as db from "./db";
import {
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
  timetables,
  studentDocuments,
  feeStructures,
  teacherComments,
  teacherSubjects,
} from "../drizzle/schema";
import { eq, and } from "drizzle-orm";

// Helper to check user role
function hasRole(userRole: string, requiredRoles: string[]) {
  return requiredRoles.includes(userRole);
}

// Role-specific procedures
const adminProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (ctx.user.role !== "admin") {
    throw new TRPCError({ code: "FORBIDDEN", message: "Admin access required" });
  }
  return next({ ctx });
});

const headmasterProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["admin", "headmaster"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Headmaster access required" });
  }
  return next({ ctx });
});

const teacherProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["admin", "teacher"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Teacher access required" });
  }
  return next({ ctx });
});

const bursarProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["admin", "bursar"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Bursar access required" });
  }
  return next({ ctx });
});

const staffProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["admin", "headmaster", "teacher", "bursar"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Staff access required" });
  }
  return next({ ctx });
});

const parentProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["parent"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Parent access required" });
  }
  return next({ ctx });
});

const studentProcedure = protectedProcedure.use(({ ctx, next }) => {
  if (!hasRole(ctx.user.role, ["student"])) {
    throw new TRPCError({ code: "FORBIDDEN", message: "Student access required" });
  }
  return next({ ctx });
});

export const appRouter = router({
  system: systemRouter,

  auth: router({
    me: publicProcedure.query((opts) => opts.ctx.user),
    logout: publicProcedure.mutation(({ ctx }) => {
      const cookieOptions = getSessionCookieOptions(ctx.req);
      ctx.res.clearCookie(COOKIE_NAME, { ...cookieOptions, maxAge: -1 });
      return { success: true } as const;
    }),
  }),

  // ============ ADMIN MANAGEMENT ============
  admin: router({
    promoteToAdmin: adminProcedure
      .input(z.object({ userId: z.number() }))
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database
          .update(users)
          .set({ role: "admin" })
          .where(eq(users.id, input.userId));

        return { success: true };
      }),

    listAllUsers: adminProcedure.query(async () => {
      const database = await db.getDb();
      if (!database) return [];
      return await database.select().from(users);
    }),

    updateUserRole: adminProcedure
      .input(z.object({ userId: z.number(), role: z.string() }))
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database
          .update(users)
          .set({ role: input.role as any })
          .where(eq(users.id, input.userId));

        return { success: true };
      }),
  }),

  // ============ STAFF MANAGEMENT ============
  staff: router({
    createStaff: adminProcedure
      .input(
        z.object({
          userId: z.number(),
          staffId: z.string(),
          firstName: z.string(),
          lastName: z.string(),
          email: z.string().email(),
          phone: z.string().optional(),
          department: z.string().optional(),
          position: z.string().optional(),
          roles: z.array(z.string()).default([]),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(staff).values({
          userId: input.userId,
          staffId: input.staffId,
          firstName: input.firstName,
          lastName: input.lastName,
          email: input.email,
          phone: input.phone,
          department: input.department,
          position: input.position,
          roles: input.roles,
        });

        return { success: true };
      }),

    getStaffList: staffProcedure.query(async () => {
      return await db.getAllStaff();
    }),

    getStaffById: staffProcedure
      .input(z.object({ id: z.number() }))
      .query(async ({ input }) => {
        return await db.getStaffById(input.id);
      }),
  }),

  // ============ STUDENT MANAGEMENT ============
  students: router({
    createStudent: staffProcedure
      .input(
        z.object({
          admissionNumber: z.string(),
          firstName: z.string(),
          lastName: z.string(),
          dateOfBirth: z.date().optional(),
          gender: z.enum(["male", "female", "other"]).optional(),
          classId: z.number().optional(),
          admissionDate: z.date(),
          bloodType: z.string().optional(),
          allergies: z.string().optional(),
          medicalConditions: z.string().optional(),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        const result = await database.insert(students).values({
          admissionNumber: input.admissionNumber,
          firstName: input.firstName,
          lastName: input.lastName,
          dateOfBirth: input.dateOfBirth,
          gender: input.gender,
          classId: input.classId,
          admissionDate: input.admissionDate,
          bloodType: input.bloodType,
          allergies: input.allergies,
          medicalConditions: input.medicalConditions,
          status: "active",
        });

        return { success: true, id: result[0] };
      }),

    getStudentById: protectedProcedure
      .input(z.object({ id: z.number() }))
      .query(async ({ input }) => {
        return await db.getStudentById(input.id);
      }),

    getStudentsByClass: staffProcedure
      .input(z.object({ classId: z.number() }))
      .query(async ({ input }) => {
        return await db.getStudentsByClass(input.classId);
      }),

    getStudentGuardians: protectedProcedure
      .input(z.object({ studentId: z.number() }))
      .query(async ({ input }) => {
        return await db.getGuardiansByStudent(input.studentId);
      }),
  }),

  // ============ GUARDIANS/PARENTS ============
  guardians: router({
    addGuardian: staffProcedure
      .input(
        z.object({
          studentId: z.number(),
          firstName: z.string(),
          lastName: z.string(),
          email: z.string().email().optional(),
          phone: z.string(),
          relationship: z.string(),
          address: z.string().optional(),
          city: z.string().optional(),
          country: z.string().optional(),
          occupation: z.string().optional(),
          isPrimaryContact: z.boolean().default(false),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(guardians).values({
          studentId: input.studentId,
          firstName: input.firstName,
          lastName: input.lastName,
          email: input.email,
          phone: input.phone,
          relationship: input.relationship,
          address: input.address,
          city: input.city,
          country: input.country,
          occupation: input.occupation,
          isPrimaryContact: input.isPrimaryContact,
        });

        return { success: true };
      }),
  }),

  // ============ CLASSES & SUBJECTS ============
  classes: router({
    createClass: adminProcedure
      .input(
        z.object({
          className: z.string(),
          stream: z.string().optional(),
          classTeacherId: z.number().optional(),
          academicYear: z.string(),
          capacity: z.number().optional(),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(classes).values(input);
        return { success: true };
      }),

    getClassesByYear: staffProcedure
      .input(z.object({ academicYear: z.string() }))
      .query(async ({ input }) => {
        return await db.getClassesByAcademicYear(input.academicYear);
      }),
  }),

  subjects: router({
    createSubject: adminProcedure
      .input(
        z.object({
          subjectName: z.string(),
          subjectCode: z.string(),
          description: z.string().optional(),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(subjects).values(input);
        return { success: true };
      }),

    getAllSubjects: protectedProcedure.query(async () => {
      return await db.getAllSubjects();
    }),
  }),

  // ============ ADMISSIONS ============
  applications: router({
    submitApplication: publicProcedure
      .input(
        z.object({
          firstName: z.string(),
          lastName: z.string(),
          email: z.string().email(),
          phone: z.string(),
          dateOfBirth: z.date(),
          guardianName: z.string(),
          guardianEmail: z.string().email(),
          guardianPhone: z.string(),
          intendedClass: z.string(),
          academicYear: z.string(),
        })
      )
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        const appNumber = `APP-${Date.now()}`;
        await database.insert(applications).values({
          applicationNumber: appNumber,
          ...input,
          status: "pending",
        });

        return { success: true, applicationNumber: appNumber };
      }),

    getPendingApplications: headmasterProcedure.query(async () => {
      return await db.getPendingApplications();
    }),

    approveApplication: headmasterProcedure
      .input(z.object({ applicationId: z.number() }))
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database
          .update(applications)
          .set({ status: "approved", processedAt: new Date() })
          .where(eq(applications.id, input.applicationId));

        return { success: true };
      }),

    rejectApplication: headmasterProcedure
      .input(z.object({ applicationId: z.number(), reason: z.string() }))
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database
          .update(applications)
          .set({ status: "rejected", rejectionReason: input.reason, processedAt: new Date() })
          .where(eq(applications.id, input.applicationId));

        return { success: true };
      }),
  }),

  // ============ FINANCE/BURSAR ============
  finance: router({
    getStudentFees: protectedProcedure
      .input(z.object({ studentId: z.number() }))
      .query(async ({ input }) => {
        return await db.getStudentFeesByStudent(input.studentId);
      }),

    recordPayment: bursarProcedure
      .input(
        z.object({
          studentId: z.number(),
          studentFeeId: z.number(),
          amount: z.string(),
          paymentMethod: z.enum(["cash", "bank_transfer", "check", "online"]),
          paymentDate: z.date(),
          notes: z.string().optional(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        const paymentNumber = `PAY-${Date.now()}`;
        const receiptNumber = `REC-${Date.now()}`;

        await database.insert(payments).values({
          paymentNumber,
          studentId: input.studentId,
          studentFeeId: input.studentFeeId,
          amount: input.amount as any,
          paymentMethod: input.paymentMethod,
          paymentDate: input.paymentDate,
          receiptNumber,
          notes: input.notes,
          recordedBy: ctx.user.id,
        });

        return { success: true, paymentNumber, receiptNumber };
      }),

    getPaymentHistory: protectedProcedure
      .input(z.object({ studentId: z.number() }))
      .query(async ({ input }) => {
        return await db.getPaymentsByStudent(input.studentId);
      }),
  }),

  // ============ ACADEMICS ============
  academics: router({
    recordMarks: teacherProcedure
      .input(
        z.object({
          studentId: z.number(),
          classId: z.number(),
          subjectId: z.number(),
          academicYear: z.string(),
          term: z.enum(["term1", "term2", "term3"]),
          assessmentType: z.enum(["weekly_test", "monthly_test", "assignment", "exam", "project"]),
          marks: z.string(),
          totalMarks: z.string(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        const marksNum = parseFloat(input.marks);
        const totalNum = parseFloat(input.totalMarks);
        const percentage = (marksNum / totalNum) * 100;

        await database.insert(academicProgress).values({
          studentId: input.studentId,
          classId: input.classId,
          subjectId: input.subjectId,
          academicYear: input.academicYear,
          term: input.term,
          assessmentType: input.assessmentType,
          marks: input.marks as any,
          totalMarks: input.totalMarks as any,
          percentage: percentage.toString() as any,
          grade: percentage >= 80 ? "A" : percentage >= 70 ? "B" : percentage >= 60 ? "C" : "D",
          recordedBy: ctx.user.id,
        });

        return { success: true };
      }),

    getStudentProgress: protectedProcedure
      .input(z.object({ studentId: z.number(), academicYear: z.string() }))
      .query(async ({ input }) => {
        return await db.getAcademicProgressByStudent(input.studentId, input.academicYear);
      }),

    addTeacherComment: teacherProcedure
      .input(
        z.object({
          studentId: z.number(),
          classId: z.number(),
          academicYear: z.string(),
          term: z.enum(["term1", "term2", "term3"]),
          progress: z.string().optional(),
          participation: z.string().optional(),
          homework: z.string().optional(),
          behaviour: z.string().optional(),
          areasForImprovement: z.string().optional(),
          strengths: z.string().optional(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(teacherComments).values({
          studentId: input.studentId,
          classId: input.classId,
          teacherId: ctx.user.id,
          academicYear: input.academicYear,
          term: input.term,
          progress: input.progress,
          participation: input.participation,
          homework: input.homework,
          behaviour: input.behaviour,
          areasForImprovement: input.areasForImprovement,
          strengths: input.strengths,
        });

        return { success: true };
      }),
  }),

  // ============ ATTENDANCE ============
  attendance: router({
    markAttendance: teacherProcedure
      .input(
        z.object({
          studentId: z.number(),
          classId: z.number(),
          date: z.date(),
          status: z.enum(["present", "absent", "late", "excused", "sick", "early_departure"]),
          remarks: z.string().optional(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(attendance).values({
          studentId: input.studentId,
          classId: input.classId,
          date: input.date,
          status: input.status,
          remarks: input.remarks,
          recordedBy: ctx.user.id,
        });

        return { success: true };
      }),

    getStudentAttendance: protectedProcedure
      .input(z.object({ studentId: z.number(), fromDate: z.date(), toDate: z.date() }))
      .query(async ({ input }) => {
        return await db.getAttendanceByStudent(input.studentId, input.fromDate, input.toDate);
      }),
  }),

  // ============ BEHAVIOUR & DISCIPLINE ============
  behaviour: router({
    recordBehaviourIssue: staffProcedure
      .input(
        z.object({
          studentId: z.number(),
          classId: z.number(),
          issueDate: z.date(),
          issueType: z.string(),
          severity: z.enum(["minor", "moderate", "severe"]),
          description: z.string(),
          action: z.string().optional(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(behaviourRecords).values({
          studentId: input.studentId,
          classId: input.classId,
          issueDate: input.issueDate,
          issueType: input.issueType,
          severity: input.severity,
          description: input.description,
          action: input.action,
          recordedBy: ctx.user.id,
        });

        return { success: true };
      }),

    getStudentBehaviourHistory: protectedProcedure
      .input(z.object({ studentId: z.number() }))
      .query(async ({ input }) => {
        return await db.getBehaviourRecordsByStudent(input.studentId);
      }),
  }),

  // ============ NOTIFICATIONS ============
  notifications: router({
    getNotifications: protectedProcedure.query(async ({ ctx }) => {
      return await db.getNotificationsByUser(ctx.user.id);
    }),

    getUnreadCount: protectedProcedure.query(async ({ ctx }) => {
      const unread = await db.getUnreadNotifications(ctx.user.id);
      return { count: unread.length };
    }),

    markAsRead: protectedProcedure
      .input(z.object({ notificationId: z.number() }))
      .mutation(async ({ input }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database
          .update(notifications)
          .set({ isRead: true })
          .where(eq(notifications.id, input.notificationId));

        return { success: true };
      }),
  }),

  // ============ ANNOUNCEMENTS ============
  announcements: router({
    getAnnouncements: publicProcedure.query(async () => {
      return await db.getActiveAnnouncements();
    }),

    createAnnouncement: headmasterProcedure
      .input(
        z.object({
          title: z.string(),
          content: z.string(),
          audience: z.enum(["all", "parents", "students", "staff", "specific_class"]),
          targetClassId: z.number().optional(),
        })
      )
      .mutation(async ({ input, ctx }) => {
        const database = await db.getDb();
        if (!database) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });

        await database.insert(announcements).values({
          title: input.title,
          content: input.content,
          audience: input.audience,
          targetClassId: input.targetClassId,
          createdBy: ctx.user.id,
          isActive: true,
        });

        return { success: true };
      }),
  }),
});

export type AppRouter = typeof appRouter;
