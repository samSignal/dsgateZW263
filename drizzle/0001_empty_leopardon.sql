CREATE TABLE `academic_progress` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`classId` int NOT NULL,
	`subjectId` int NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`term` enum('term1','term2','term3') NOT NULL,
	`assessmentType` enum('weekly_test','monthly_test','assignment','exam','project') NOT NULL,
	`marks` decimal(5,2),
	`totalMarks` decimal(5,2),
	`percentage` decimal(5,2),
	`grade` varchar(5),
	`recordedBy` int NOT NULL,
	`recordedAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `academic_progress_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `announcements` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255) NOT NULL,
	`content` text NOT NULL,
	`audience` enum('all','parents','students','staff','specific_class') NOT NULL DEFAULT 'all',
	`targetClassId` int,
	`createdBy` int NOT NULL,
	`isActive` boolean NOT NULL DEFAULT true,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `announcements_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `applications` (
	`id` int AUTO_INCREMENT NOT NULL,
	`applicationNumber` varchar(50) NOT NULL,
	`firstName` varchar(100) NOT NULL,
	`lastName` varchar(100) NOT NULL,
	`email` varchar(320) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`dateOfBirth` date NOT NULL,
	`guardianName` varchar(100) NOT NULL,
	`guardianEmail` varchar(320) NOT NULL,
	`guardianPhone` varchar(20) NOT NULL,
	`intendedClass` varchar(100) NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`status` enum('pending','approved','rejected','enrolled') NOT NULL DEFAULT 'pending',
	`rejectionReason` text,
	`processedBy` int,
	`processedAt` timestamp,
	`enrolledStudentId` int,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `applications_id` PRIMARY KEY(`id`),
	CONSTRAINT `applications_applicationNumber_unique` UNIQUE(`applicationNumber`)
);
--> statement-breakpoint
CREATE TABLE `attendance` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`classId` int NOT NULL,
	`date` date NOT NULL,
	`status` enum('present','absent','late','excused','sick','early_departure') NOT NULL,
	`remarks` text,
	`recordedBy` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `attendance_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `behaviour_records` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`classId` int NOT NULL,
	`issueDate` date NOT NULL,
	`issueType` varchar(100) NOT NULL,
	`severity` enum('minor','moderate','severe') NOT NULL,
	`description` text NOT NULL,
	`action` varchar(100),
	`parentMeetingScheduled` boolean NOT NULL DEFAULT false,
	`parentMeetingDate` date,
	`parentMeetingNotes` text,
	`headmasterReview` text,
	`reviewedAt` timestamp,
	`recordedBy` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `behaviour_records_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `classes` (
	`id` int AUTO_INCREMENT NOT NULL,
	`className` varchar(100) NOT NULL,
	`stream` varchar(50),
	`classTeacherId` int,
	`academicYear` varchar(20) NOT NULL,
	`capacity` int,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `classes_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `fee_structures` (
	`id` int AUTO_INCREMENT NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`className` varchar(100) NOT NULL,
	`term` enum('term1','term2','term3') NOT NULL,
	`amount` decimal(12,2) NOT NULL,
	`dueDate` date NOT NULL,
	`description` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `fee_structures_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `guardians` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int,
	`studentId` int NOT NULL,
	`firstName` varchar(100) NOT NULL,
	`lastName` varchar(100) NOT NULL,
	`email` varchar(320),
	`phone` varchar(20) NOT NULL,
	`relationship` varchar(50) NOT NULL,
	`address` text,
	`city` varchar(100),
	`country` varchar(100),
	`occupation` varchar(100),
	`isPrimaryContact` boolean NOT NULL DEFAULT false,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `guardians_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `notifications` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int NOT NULL,
	`type` enum('fee_reminder','fee_overdue','attendance_alert','behaviour_warning','academic_progress','announcement') NOT NULL,
	`title` varchar(255) NOT NULL,
	`message` text NOT NULL,
	`relatedStudentId` int,
	`relatedRecordId` int,
	`isRead` boolean NOT NULL DEFAULT false,
	`emailSent` boolean NOT NULL DEFAULT false,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `notifications_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `payments` (
	`id` int AUTO_INCREMENT NOT NULL,
	`paymentNumber` varchar(50) NOT NULL,
	`studentId` int NOT NULL,
	`studentFeeId` int NOT NULL,
	`amount` decimal(12,2) NOT NULL,
	`paymentMethod` enum('cash','bank_transfer','check','online') NOT NULL,
	`paymentDate` date NOT NULL,
	`receiptNumber` varchar(50),
	`notes` text,
	`recordedBy` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `payments_id` PRIMARY KEY(`id`),
	CONSTRAINT `payments_paymentNumber_unique` UNIQUE(`paymentNumber`),
	CONSTRAINT `payments_receiptNumber_unique` UNIQUE(`receiptNumber`)
);
--> statement-breakpoint
CREATE TABLE `reports` (
	`id` int AUTO_INCREMENT NOT NULL,
	`reportName` varchar(255) NOT NULL,
	`reportType` enum('fees','academics','attendance','behaviour','purchases','student_record') NOT NULL,
	`format` enum('pdf','excel') NOT NULL,
	`fileUrl` text NOT NULL,
	`fileKey` text NOT NULL,
	`generatedBy` int NOT NULL,
	`filters` json NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `reports_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `staff` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int NOT NULL,
	`staffId` varchar(50) NOT NULL,
	`firstName` varchar(100) NOT NULL,
	`lastName` varchar(100) NOT NULL,
	`email` varchar(320) NOT NULL,
	`phone` varchar(20),
	`department` varchar(100),
	`position` varchar(100),
	`roles` json NOT NULL DEFAULT ('[]'),
	`qualifications` text,
	`employmentDate` date,
	`isActive` boolean NOT NULL DEFAULT true,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `staff_id` PRIMARY KEY(`id`),
	CONSTRAINT `staff_staffId_unique` UNIQUE(`staffId`)
);
--> statement-breakpoint
CREATE TABLE `student_documents` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`documentType` varchar(100) NOT NULL,
	`fileName` varchar(255) NOT NULL,
	`fileUrl` text NOT NULL,
	`fileKey` text NOT NULL,
	`uploadedAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `student_documents_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `student_fees` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`term` enum('term1','term2','term3') NOT NULL,
	`amount` decimal(12,2) NOT NULL,
	`amountPaid` decimal(12,2) NOT NULL DEFAULT 0,
	`balance` decimal(12,2) NOT NULL,
	`dueDate` date NOT NULL,
	`status` enum('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `student_fees_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `student_purchases` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`purchaseDate` date NOT NULL,
	`itemType` varchar(100) NOT NULL,
	`description` varchar(255) NOT NULL,
	`quantity` int NOT NULL DEFAULT 1,
	`unitPrice` decimal(10,2) NOT NULL,
	`totalPrice` decimal(10,2) NOT NULL,
	`recordedBy` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `student_purchases_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `students` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int,
	`admissionNumber` varchar(50) NOT NULL,
	`firstName` varchar(100) NOT NULL,
	`lastName` varchar(100) NOT NULL,
	`email` varchar(320),
	`dateOfBirth` date,
	`gender` enum('male','female','other'),
	`classId` int,
	`admissionDate` date NOT NULL,
	`status` enum('active','inactive','transferred','graduated','suspended') NOT NULL DEFAULT 'active',
	`bloodType` varchar(10),
	`allergies` text,
	`medicalConditions` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `students_id` PRIMARY KEY(`id`),
	CONSTRAINT `students_admissionNumber_unique` UNIQUE(`admissionNumber`)
);
--> statement-breakpoint
CREATE TABLE `subjects` (
	`id` int AUTO_INCREMENT NOT NULL,
	`subjectName` varchar(100) NOT NULL,
	`subjectCode` varchar(20) NOT NULL,
	`description` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `subjects_id` PRIMARY KEY(`id`),
	CONSTRAINT `subjects_subjectName_unique` UNIQUE(`subjectName`),
	CONSTRAINT `subjects_subjectCode_unique` UNIQUE(`subjectCode`)
);
--> statement-breakpoint
CREATE TABLE `teacher_comments` (
	`id` int AUTO_INCREMENT NOT NULL,
	`studentId` int NOT NULL,
	`classId` int NOT NULL,
	`teacherId` int NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`term` enum('term1','term2','term3') NOT NULL,
	`progress` text,
	`participation` text,
	`homework` text,
	`behaviour` text,
	`areasForImprovement` text,
	`strengths` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `teacher_comments_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `teacher_subjects` (
	`id` int AUTO_INCREMENT NOT NULL,
	`staffId` int NOT NULL,
	`classId` int NOT NULL,
	`subjectId` int NOT NULL,
	`academicYear` varchar(20) NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `teacher_subjects_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `timetables` (
	`id` int AUTO_INCREMENT NOT NULL,
	`classId` int NOT NULL,
	`dayOfWeek` enum('monday','tuesday','wednesday','thursday','friday') NOT NULL,
	`periodNumber` int NOT NULL,
	`subjectId` int NOT NULL,
	`teacherId` int NOT NULL,
	`startTime` varchar(10) NOT NULL,
	`endTime` varchar(10) NOT NULL,
	`room` varchar(50),
	`academicYear` varchar(20) NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `timetables_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
ALTER TABLE `users` MODIFY COLUMN `role` enum('admin','headmaster','teacher','bursar','parent','student','user') NOT NULL DEFAULT 'user';--> statement-breakpoint
ALTER TABLE `users` ADD `phone` varchar(20);--> statement-breakpoint
ALTER TABLE `users` ADD `isActive` boolean DEFAULT true NOT NULL;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_email_unique` UNIQUE(`email`);--> statement-breakpoint
CREATE INDEX `ap_studentId_idx` ON `academic_progress` (`studentId`);--> statement-breakpoint
CREATE INDEX `ap_classId_idx` ON `academic_progress` (`classId`);--> statement-breakpoint
CREATE INDEX `ap_subjectId_idx` ON `academic_progress` (`subjectId`);--> statement-breakpoint
CREATE INDEX `ap_term_idx` ON `academic_progress` (`term`);--> statement-breakpoint
CREATE INDEX `ann_audience_idx` ON `announcements` (`audience`);--> statement-breakpoint
CREATE INDEX `app_status_idx` ON `applications` (`status`);--> statement-breakpoint
CREATE INDEX `app_academicYear_idx` ON `applications` (`academicYear`);--> statement-breakpoint
CREATE INDEX `att_studentId_idx` ON `attendance` (`studentId`);--> statement-breakpoint
CREATE INDEX `att_classId_idx` ON `attendance` (`classId`);--> statement-breakpoint
CREATE INDEX `att_date_idx` ON `attendance` (`date`);--> statement-breakpoint
CREATE INDEX `br_studentId_idx` ON `behaviour_records` (`studentId`);--> statement-breakpoint
CREATE INDEX `br_issueDate_idx` ON `behaviour_records` (`issueDate`);--> statement-breakpoint
CREATE INDEX `class_className_idx` ON `classes` (`className`);--> statement-breakpoint
CREATE INDEX `class_academicYear_idx` ON `classes` (`academicYear`);--> statement-breakpoint
CREATE INDEX `fs_academicYear_idx` ON `fee_structures` (`academicYear`);--> statement-breakpoint
CREATE INDEX `guardian_studentId_idx` ON `guardians` (`studentId`);--> statement-breakpoint
CREATE INDEX `guardian_userId_idx` ON `guardians` (`userId`);--> statement-breakpoint
CREATE INDEX `notif_userId_idx` ON `notifications` (`userId`);--> statement-breakpoint
CREATE INDEX `notif_type_idx` ON `notifications` (`type`);--> statement-breakpoint
CREATE INDEX `payment_studentId_idx` ON `payments` (`studentId`);--> statement-breakpoint
CREATE INDEX `payment_paymentDate_idx` ON `payments` (`paymentDate`);--> statement-breakpoint
CREATE INDEX `rep_reportType_idx` ON `reports` (`reportType`);--> statement-breakpoint
CREATE INDEX `staff_userId_idx` ON `staff` (`userId`);--> statement-breakpoint
CREATE INDEX `staff_staffId_idx` ON `staff` (`staffId`);--> statement-breakpoint
CREATE INDEX `doc_studentId_idx` ON `student_documents` (`studentId`);--> statement-breakpoint
CREATE INDEX `sf_studentId_idx` ON `student_fees` (`studentId`);--> statement-breakpoint
CREATE INDEX `sf_academicYear_idx` ON `student_fees` (`academicYear`);--> statement-breakpoint
CREATE INDEX `sf_status_idx` ON `student_fees` (`status`);--> statement-breakpoint
CREATE INDEX `sp_studentId_idx` ON `student_purchases` (`studentId`);--> statement-breakpoint
CREATE INDEX `sp_purchaseDate_idx` ON `student_purchases` (`purchaseDate`);--> statement-breakpoint
CREATE INDEX `student_admissionNumber_idx` ON `students` (`admissionNumber`);--> statement-breakpoint
CREATE INDEX `student_classId_idx` ON `students` (`classId`);--> statement-breakpoint
CREATE INDEX `student_status_idx` ON `students` (`status`);--> statement-breakpoint
CREATE INDEX `tc_studentId_idx` ON `teacher_comments` (`studentId`);--> statement-breakpoint
CREATE INDEX `tc_classId_idx` ON `teacher_comments` (`classId`);--> statement-breakpoint
CREATE INDEX `ts_staffId_idx` ON `teacher_subjects` (`staffId`);--> statement-breakpoint
CREATE INDEX `ts_classId_idx` ON `teacher_subjects` (`classId`);--> statement-breakpoint
CREATE INDEX `ts_subjectId_idx` ON `teacher_subjects` (`subjectId`);--> statement-breakpoint
CREATE INDEX `tt_classId_idx` ON `timetables` (`classId`);--> statement-breakpoint
CREATE INDEX `tt_dayOfWeek_idx` ON `timetables` (`dayOfWeek`);--> statement-breakpoint
CREATE INDEX `tt_periodNumber_idx` ON `timetables` (`periodNumber`);--> statement-breakpoint
CREATE INDEX `email_idx` ON `users` (`email`);--> statement-breakpoint
CREATE INDEX `role_idx` ON `users` (`role`);