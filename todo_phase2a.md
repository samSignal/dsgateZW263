PHASE 2A status

Implemented
- AdmissionReviewController::enroll updated to stream-first populate students.stream_id/form_id/category_id/academic_year_id
- StudentSubjectController::studentStream updated to stream-first fallback to class_id
- Smoke test added/updated: StudentStreamEnrollmentSmokeTest

Not yet implemented
- Full end-to-end enrollment test via controller accept+enroll pipeline
- Subject auto-enrollment verification tests
- Capacity stress test
- Backward compatibility test
- Parent/guardian and student account creation smoke tests
- Stream-based retrieval test
- Legacy fallback test
- Legacy system impact scan summary (report by module)

