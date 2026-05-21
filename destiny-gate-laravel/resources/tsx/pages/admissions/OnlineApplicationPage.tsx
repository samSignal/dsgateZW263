import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Btn, Card, CardBody, CardHeader, FormGroup, Input, Select, Grid, PageHeader, Spinner, Alert, Textarea, Divider, Code } from '../../components/UI';

const STEPS = [
  'Application Type',
  'Student Details',
  'Guardian Details',
  'Academic Background',
  'Required Documents',
  'Review & Submit'
];

export default function OnlineApplicationPage() {
  const navigate = useNavigate();
  const { token: resumeToken } = useParams();
  const [searchParams] = useSearchParams();
  const typeParam = searchParams.get('type');
  
  const [step, setStep] = useState(1);
  const [token, setToken] = useState(resumeToken || null);
  const [loading, setLoading] = useState(!!resumeToken);
  const [files, setFiles] = useState<Record<string, File>>({});
  const [uploadedDocs, setUploadedDocs] = useState<any[]>([]);
  
  const [form, setForm] = useState({
    application_type: typeParam === 'transfer' ? 'transfer' : 'new_intake',
    student_first_name: '',
    student_last_name: '',
    gender: '',
    date_of_birth: '',
    birth_certificate_number: '',
    student_national_id: '',
    guardian_name: '',
    guardian_national_id: '',
    guardian_phone: '',
    emergency_phone: '',
    guardian_email: '',
    address: '',
    occupation: '',
    applying_form_id: '',
    academic_year_id: '',
    grade7_school: '',
    grade7_results: '',
    previous_school_name: '',
    current_form: '',
    transfer_reason: '',
    last_term_average: '',
    medical_information: '',
    reason_for_joining: '',
  });

  const { data: years = [] } = useQuery({ queryKey: ['admission-years'], queryFn: () => api.get('/admissions/academic-years').then(r => r.data) });
  const { data: forms = [] } = useQuery({ queryKey: ['admission-forms'], queryFn: () => api.get('/admissions/forms').then(r => r.data) });

  useEffect(() => {
    if (resumeToken) {
      api.get(`/admissions/resume/${resumeToken}`).then(r => {
        setForm(prev => ({ ...prev, ...r.data.application }));
        setStep(r.data.application.current_step || 1);
        setUploadedDocs(r.data.documents || []);
        setToken(resumeToken);
        setLoading(false);
      }).catch(() => {
        toastError('Invalid or expired token.');
        navigate('/admissions');
      });
    }
  }, [resumeToken]);

  const saveDraft = useMutation({
    mutationFn: (data: any) => api.post('/admissions/draft', { ...data, tracking_token: token, current_step: step }),
    onSuccess: (r) => {
      if (!token) setToken(r.data.tracking_token);
    }
  });

  const [uploading, setUploading] = useState<string | null>(null);

  const uploadDoc = useMutation({
    mutationFn: ({ type, file }: { type: string, file: File }) => {
      if (!token) {
        toastError('Please save your progress first before uploading documents.');
        throw new Error('No token');
      }
      setUploading(type);
      const fd = new FormData();
      fd.append('document', file);
      fd.append('document_type', type);
      return api.post(`/admissions/upload/${token}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then(r => ({ ...r.data, type }));
    },
    onSuccess: (data) => {
      toastSuccess('Document uploaded successfully.');
      setUploadedDocs(prev => {
        const filtered = prev.filter(d => d.document_type !== data.type);
        return [...filtered, { document_type: data.type, uploaded_at: new Date().toISOString() }];
      });
    },
    onError: (e: any) => {
      const msg = e.response?.data?.message || e.message || 'Upload failed.';
      toastError(msg);
    },
    onSettled: () => setUploading(null)
  });

  const submitApplication = useMutation({
    mutationFn: (data: any) => api.post('/admissions/apply', { ...data, tracking_token: token }),
    onSuccess: (r) => {
      toastSuccess('Application submitted successfully!');
      navigate(`/admissions/track?token=${r.data.tracking_token}`);
    },
    onError: (e: any) => toastError(e.response?.data?.message || 'Submission failed. Please check all fields.')
  });

  const handleNext = () => {
    if (step === 5) {
      // Logic for step 5 upload if needed
    }
    saveDraft.mutate(form);
    setStep(step + 1);
    window.scrollTo(0,0);
  };

  if (loading) return <Spinner />;

  const isTransfer = form.application_type === 'transfer';

  return (
    <div style={{ minHeight: '100vh', background: '#f1f5f9', padding: '40px 20px' }}>
      <div style={{ maxWidth: 800, margin: '0 auto' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 32 }}>
          <img src="/logo.svg" alt="DGI" style={{ width: 40, height: 40 }} />
          <div>
            <h1 style={{ fontSize: 18, fontWeight: 800, color: '#0f172a', margin: 0 }}>DestinyGate Institute</h1>
            <p style={{ fontSize: 12, color: '#64748b', margin: 0 }}>Online Admission System</p>
          </div>
        </div>

        <div style={{ marginBottom: 32 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12 }}>
            {STEPS.map((s, i) => (
              <div key={s} style={{ 
                flex: 1, height: 4, borderRadius: 2, margin: '0 2px',
                background: step > i ? '#1a6b3c' : step === i + 1 ? '#fbbf24' : '#e2e8f0' 
              }} />
            ))}
          </div>
          <div style={{ fontSize: 13, fontWeight: 700, color: '#1a6b3c', textTransform: 'uppercase', letterSpacing: '.5px' }}>
            Step {step}: {STEPS[step-1]}
          </div>
        </div>

        <Card style={{ border: 'none', boxShadow: '0 10px 30px rgba(0,0,0,.04)' }}>
          <CardBody style={{ padding: 40 }}>
            {step === 1 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 8 }}>Application Type</h2>
                <p style={{ color: '#64748b', fontSize: 14, marginBottom: 32 }}>Select the appropriate category for this application.</p>
                
                <Grid cols={2} style={{ gap: 20, marginBottom: 32 }}>
                  <div 
                    onClick={() => setForm({...form, application_type: 'new_intake'})}
                    style={{ 
                      padding: 24, borderRadius: 12, border: `2px solid ${form.application_type === 'new_intake' ? '#1a6b3c' : '#f1f5f9'}`,
                      background: form.application_type === 'new_intake' ? '#f0faf4' : '#fff', cursor: 'pointer', transition: 'all .2s'
                    }}
                  >
                    <div style={{ fontSize: 32, marginBottom: 12 }}>🎓</div>
                    <div style={{ fontWeight: 700, color: '#0f172a' }}>New Intake</div>
                    <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>Standard Form 1 entry from Grade 7.</div>
                  </div>
                  <div 
                    onClick={() => setForm({...form, application_type: 'transfer'})}
                    style={{ 
                      padding: 24, borderRadius: 12, border: `2px solid ${form.application_type === 'transfer' ? '#1a6b3c' : '#f1f5f9'}`,
                      background: form.application_type === 'transfer' ? '#f0faf4' : '#fff', cursor: 'pointer', transition: 'all .2s'
                    }}
                  >
                    <div style={{ fontSize: 32, marginBottom: 12 }}>🔄</div>
                    <div style={{ fontWeight: 700, color: '#0f172a' }}>Transfer</div>
                    <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>Moving from another secondary school.</div>
                  </div>
                </Grid>

                <Grid cols={2}>
                  <FormGroup label="Academic Year">
                    <Select value={form.academic_year_id} onChange={e => setForm({...form, academic_year_id: e.target.value})}>
                      <option value="">Select year…</option>
                      {years.map((y: any) => <option key={y.id} value={y.id}>{y.name}</option>)}
                    </Select>
                  </FormGroup>
                  <FormGroup label="Applying For Form">
                    <Select value={form.applying_form_id} onChange={e => setForm({...form, applying_form_id: e.target.value})}>
                      <option value="">Select form…</option>
                      {forms.map((f: any) => <option key={f.id} value={f.id}>{f.name}</option>)}
                    </Select>
                  </FormGroup>
                </Grid>
              </div>
            )}

            {step === 2 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 32 }}>Student Details</h2>
                <Grid cols={2}>
                  <FormGroup label="First Name"><Input value={form.student_first_name} onChange={e => setForm({...form, student_first_name: e.target.value})} /></FormGroup>
                  <FormGroup label="Last Name"><Input value={form.student_last_name} onChange={e => setForm({...form, student_last_name: e.target.value})} /></FormGroup>
                </Grid>
                <Grid cols={2}>
                  <FormGroup label="Gender">
                    <Select value={form.gender} onChange={e => setForm({...form, gender: e.target.value})}>
                      <option value="">Select…</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </Select>
                  </FormGroup>
                  <FormGroup label="Date of Birth"><Input type="date" value={form.date_of_birth} onChange={e => setForm({...form, date_of_birth: e.target.value})} /></FormGroup>
                </Grid>
                <Grid cols={2}>
                  <FormGroup label="Birth Certificate #"><Input value={form.birth_certificate_number} onChange={e => setForm({...form, birth_certificate_number: e.target.value})} /></FormGroup>
                  <FormGroup label="National ID (If applicable)"><Input value={form.student_national_id} onChange={e => setForm({...form, student_national_id: e.target.value})} /></FormGroup>
                </Grid>
              </div>
            )}

            {step === 3 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 32 }}>Guardian Information</h2>
                <Grid cols={2}>
                  <FormGroup label="Full Name"><Input value={form.guardian_name} onChange={e => setForm({...form, guardian_name: e.target.value})} /></FormGroup>
                  <FormGroup label="National ID"><Input value={form.guardian_national_id} onChange={e => setForm({...form, guardian_national_id: e.target.value})} /></FormGroup>
                </Grid>
                <Grid cols={3}>
                  <FormGroup label="Phone Number"><Input value={form.guardian_phone} onChange={e => setForm({...form, guardian_phone: e.target.value})} /></FormGroup>
                  <FormGroup label="Emergency Contact"><Input value={form.emergency_phone} onChange={e => setForm({...form, emergency_phone: e.target.value})} /></FormGroup>
                  <FormGroup label="Email Address"><Input type="email" value={form.guardian_email} onChange={e => setForm({...form, guardian_email: e.target.value})} /></FormGroup>
                </Grid>
                <FormGroup label="Residential Address"><Textarea value={form.address} onChange={e => setForm({...form, address: e.target.value})} /></FormGroup>
                <FormGroup label="Occupation"><Input value={form.occupation} onChange={e => setForm({...form, occupation: e.target.value})} /></FormGroup>
              </div>
            )}

            {step === 4 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 32 }}>Academic Background</h2>
                {!isTransfer ? (
                  <Grid cols={2}>
                    <FormGroup label="Grade 7 School"><Input value={form.grade7_school} onChange={e => setForm({...form, grade7_school: e.target.value})} /></FormGroup>
                    <FormGroup label="Grade 7 Results Summary"><Input value={form.grade7_results} onChange={e => setForm({...form, grade7_results: e.target.value})} placeholder="e.g. 4 Units" /></FormGroup>
                  </Grid>
                ) : (
                  <>
                    <Grid cols={2}>
                      <FormGroup label="Previous School"><Input value={form.previous_school_name} onChange={e => setForm({...form, previous_school_name: e.target.value})} /></FormGroup>
                      <FormGroup label="Current Form"><Input value={form.current_form} onChange={e => setForm({...form, current_form: e.target.value})} /></FormGroup>
                    </Grid>
                    <FormGroup label="Reason for Transfer"><Textarea value={form.transfer_reason} onChange={e => setForm({...form, transfer_reason: e.target.value})} /></FormGroup>
                    <FormGroup label="Last Term Average (%)"><Input value={form.last_term_average} onChange={e => setForm({...form, last_term_average: e.target.value})} /></FormGroup>
                  </>
                )}
                <Divider />
                <FormGroup label="Medical Information (Allergies, conditions, etc.)"><Textarea value={form.medical_information} onChange={e => setForm({...form, medical_information: e.target.value})} /></FormGroup>
                <FormGroup label="Reason for Joining DestinyGate"><Textarea value={form.reason_for_joining} onChange={e => setForm({...form, reason_for_joining: e.target.value})} /></FormGroup>
              </div>
            )}

            {step === 5 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 8 }}>Documents Upload</h2>
                <p style={{ color: '#64748b', fontSize: 14, marginBottom: 32 }}>Please upload clear scanned copies of the following documents (PDF, JPG, PNG, WEBP, GIF).</p>
                
                <div style={{ spaceY: 12 }}>
                  {[
                    { id: 'birth_certificate', label: 'Birth Certificate', required: true },
                    { id: 'passport_photo', label: 'Passport Sized Photo', required: true },
                    { id: 'latest_report', label: 'Latest School Report', required: true },
                    ...(isTransfer ? [{ id: 'transfer_letter', label: 'Transfer Letter', required: true }] : []),
                  ].map(doc => (
                    <div key={doc.id} style={{ padding: 20, background: '#f8fafc', borderRadius: 12, marginBottom: 12, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div>
                        <div style={{ fontSize: 14, fontWeight: 700, color: '#0f172a' }}>{doc.label} {doc.required && <span style={{ color: '#dc2626' }}>*</span>}</div>
                        <div style={{ fontSize: 12, color: '#64748b' }}>
                          {uploadedDocs.find(d => d.document_type === doc.id) ? '✅ Already Uploaded' : 'Not uploaded yet'}
                        </div>
                      </div>
                      <input 
                        type="file" id={doc.id} hidden 
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.bmp,.svg"
                        onChange={(e) => {
                          const file = e.target.files?.[0];
                          if (file) {
                            if (file.size > 5 * 1024 * 1024) {
                              toastError('File size exceeds 5MB limit.');
                              return;
                            }
                            uploadDoc.mutate({ type: doc.id, file });
                          }
                        }}
                      />
                      <Btn size="sm" variant="outline" onClick={() => document.getElementById(doc.id)?.click()} loading={uploading === doc.id}>
                        {uploadedDocs.find(d => d.document_type === doc.id) ? 'Re-upload' : 'Choose File'}
                      </Btn>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {step === 6 && (
              <div style={{ animation: 'fadeUp .3s ease' }}>
                <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', marginBottom: 8 }}>Review & Submit</h2>
                <p style={{ color: '#64748b', fontSize: 14, marginBottom: 32 }}>Please verify all information before final submission. You cannot edit after submitting.</p>
                
                <div style={{ background: '#f8fafc', borderRadius: 16, padding: 24, marginBottom: 32 }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                    <span style={{ fontSize: 13, color: '#64748b' }}>Student Name</span>
                    <span style={{ fontSize: 13, fontWeight: 700 }}>{form.student_first_name} {form.student_last_name}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                    <span style={{ fontSize: 13, color: '#64748b' }}>Application Type</span>
                    <span style={{ fontSize: 13, fontWeight: 700, textTransform: 'capitalize' }}>{form.application_type.replace('_', ' ')}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                    <span style={{ fontSize: 13, color: '#64748b' }}>Guardian Contact</span>
                    <span style={{ fontSize: 13, fontWeight: 700 }}>{form.guardian_phone}</span>
                  </div>
                  <Divider />
                  <div style={{ fontSize: 12, color: '#64748b', lineHeight: 1.6 }}>
                    By clicking submit, you certify that all information provided is true and correct to the best of your knowledge.
                  </div>
                </div>

                {token && (
                  <Alert type="success" message={`Your tracking token is: ${token}. Please keep this safe to track your progress.`} />
                )}
              </div>
            )}

            <div style={{ marginTop: 40, pt: 32, borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                {token && <Code>Token: {token}</Code>}
              </div>
              <div style={{ display: 'flex', gap: 12 }}>
                {step > 1 && <Btn variant="outline" onClick={() => setStep(step - 1)}>Previous</Btn>}
                {step < 6 ? (
                  <Btn onClick={handleNext} loading={saveDraft.isPending} style={{ background: '#1a6b3c', px: 32 }}>Save & Continue</Btn>
                ) : (
                  <Btn onClick={() => submitApplication.mutate(form)} loading={submitApplication.isPending} style={{ background: '#0f3d22', px: 40 }}>
                    Final Submit Application
                  </Btn>
                )}
              </div>
            </div>
          </CardBody>
        </Card>

        <p style={{ textAlign: 'center', marginTop: 32, fontSize: 12, color: '#94a3b8' }}>
          © {new Date().getFullYear()} DestinyGate Institute · Admissions Department
        </p>
      </div>
    </div>
  );
}
