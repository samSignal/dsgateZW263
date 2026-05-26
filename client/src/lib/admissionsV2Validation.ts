import type { V2ApplicationType, V2DraftApplication } from "@/lib/admissionsV2Api";

export type FieldErrors = Record<string, string[]>;

export function requiredDocumentsFor(type: V2ApplicationType): string[] {
  return type === "transfer"
    ? ["birth_certificate", "passport_photo", "latest_report", "transfer_letter", "discipline_record"]
    : ["birth_certificate", "passport_photo", "grade7_report"];
}

export function validateStep(step: number, draft: Partial<V2DraftApplication> & { application_type?: V2ApplicationType }): FieldErrors {
  const errors: FieldErrors = {};
  const req = (field: string, ok: boolean, msg = "Required.") => {
    if (!ok) errors[field] = [msg];
  };

  if (step === 1) {
    req("application_type", !!draft.application_type);
    req("academic_year_id", !!draft.academic_year_id);
    req("applying_form_id", !!draft.applying_form_id);
    req("preferred_category_id", !!draft.preferred_category_id);
  }

  if (step === 2) {
    req("student_first_name", !!draft.student_first_name?.trim());
    req("student_last_name", !!draft.student_last_name?.trim());
    req("gender", !!draft.gender);
    req("date_of_birth", !!draft.date_of_birth);
    req("birth_certificate_number", !!draft.birth_certificate_number?.trim());
  }

  if (step === 3) {
    req("guardian_name", !!draft.guardian_name?.trim());
    req("guardian_phone", !!draft.guardian_phone?.trim());
    req("guardian_email", !!draft.guardian_email?.trim());
    req("emergency_phone", !!draft.emergency_phone?.trim());
  }

  if (step === 4) {
    if (draft.application_type === "transfer") {
      req("previous_school_name", !!draft.previous_school_name?.trim(), "Required for transfer.");
      req("current_form", !!draft.current_form?.trim(), "Required for transfer.");
      req("transfer_reason", !!draft.transfer_reason?.trim(), "Required for transfer.");
    } else if (draft.application_type === "new_intake") {
      req("grade7_school", !!draft.grade7_school?.trim(), "Required for new intake.");
      req("grade7_results", !!draft.grade7_results?.trim(), "Required for new intake.");
    }
  }

  return errors;
}

