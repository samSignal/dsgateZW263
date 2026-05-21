import React, { useEffect, useState } from "react";
import { useRoute, useLocation } from "wouter";
import { ArrowLeft, User, FileText, CheckCircle, Clock, Save, Eye, Check, X } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";

const fetchApplication = async (id: string) => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/applicants/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Failed to fetch application');
    return res.json();
};

const updateStatus = async (id: string, status: string, remarks: string) => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/applicants/${id}/status`, {
        method: 'PATCH',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ status, remarks })
    });
    if (!res.ok) throw new Error('Failed to update status');
    return res.json();
};

const approveApplication = async (id: string) => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/applicants/${id}/approve`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Failed to approve');
    return res.json();
};

const rejectApplication = async (id: string, remarks: string) => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/applicants/${id}/reject`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ remarks })
    });
    if (!res.ok) throw new Error('Failed to reject');
    return res.json();
};

const ApplicantDetailsPage: React.FC = () => {
    const [, params] = useRoute("/app/admissions/applicants/:id");
    const [, setLocation] = useLocation();
    const id = params?.id;
    
    const [app, setApp] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [remarks, setRemarks] = useState('');
    const [statusUpdate, setStatusUpdate] = useState('');

    const loadData = () => {
        if (!id) return;
        setLoading(true);
        fetchApplication(id)
            .then(res => {
                setApp(res.data);
                setStatusUpdate(res.data.status);
                setRemarks('');
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    };

    useEffect(() => {
        loadData();
    }, [id]);

    const handleUpdateStatus = async () => {
        if (!id) return;
        try {
            await updateStatus(id, statusUpdate, remarks);
            loadData();
            alert("Status updated successfully");
        } catch (error) {
            console.error(error);
            alert("Failed to update status");
        }
    };

    const handleApprove = async () => {
        if (!id) return;
        if (!confirm("Are you sure you want to approve this application?")) return;
        try {
            await approveApplication(id);
            loadData();
            alert("Application approved");
        } catch (error) {
            console.error(error);
            alert("Failed to approve");
        }
    };

    const handleReject = async () => {
        if (!id) return;
        if (!confirm("Are you sure you want to reject this application?")) return;
        try {
            await rejectApplication(id, remarks || 'Application rejected');
            loadData();
            alert("Application rejected");
        } catch (error) {
            console.error(error);
            alert("Failed to reject");
        }
    };

    if (loading) {
        return <div className="flex justify-center py-12"><div className="animate-spin h-8 w-8 border-4 border-[#0B4619] border-t-transparent rounded-full"></div></div>;
    }

    if (!app) {
        return <div>Application not found</div>;
    }

    return (
        <div className="space-y-6 pb-12">
            <div className="flex items-center space-x-4">
                <Button variant="outline" size="icon" onClick={() => setLocation('/app/admissions/applicants')}>
                    <ArrowLeft className="h-4 w-4" />
                </Button>
                <div>
                    <h2 className="text-2xl font-bold tracking-tight text-[#0B4619]">Application #{app.application_number}</h2>
                    <p className="text-gray-500">Applicant: {app.student_first_name} {app.student_last_name}</p>
                </div>
                <div className="ml-auto">
                    <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium capitalize">
                        {app.status.replace('_', ' ')}
                    </span>
                </div>
            </div>

            <Tabs defaultValue="details" className="w-full">
                <TabsList className="mb-4">
                    <TabsTrigger value="details">Applicant Details</TabsTrigger>
                    <TabsTrigger value="documents">Documents</TabsTrigger>
                    <TabsTrigger value="status">Status & Processing</TabsTrigger>
                </TabsList>
                
                <TabsContent value="details" className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle className="text-lg">Student Information</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 text-sm">
                            <div><span className="font-semibold text-gray-500">First Name:</span> {app.student_first_name}</div>
                            <div><span className="font-semibold text-gray-500">Last Name:</span> {app.student_last_name}</div>
                            <div><span className="font-semibold text-gray-500">Gender:</span> {app.gender}</div>
                            <div><span className="font-semibold text-gray-500">Date of Birth:</span> {app.date_of_birth}</div>
                            <div><span className="font-semibold text-gray-500">Birth Cert Number:</span> {app.birth_certificate_number}</div>
                            <div><span className="font-semibold text-gray-500">National ID:</span> {app.student_national_id || 'N/A'}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-lg">Guardian Information</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 text-sm">
                            <div><span className="font-semibold text-gray-500">Guardian Name:</span> {app.guardian_name}</div>
                            <div><span className="font-semibold text-gray-500">Phone:</span> {app.guardian_phone}</div>
                            <div><span className="font-semibold text-gray-500">Email:</span> {app.guardian_email}</div>
                            <div><span className="font-semibold text-gray-500">Emergency Phone:</span> {app.emergency_phone}</div>
                            <div className="col-span-2"><span className="font-semibold text-gray-500">Address:</span> {app.address}</div>
                            <div><span className="font-semibold text-gray-500">Occupation:</span> {app.occupation}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-lg">Academic Information</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 text-sm">
                            <div><span className="font-semibold text-gray-500">Application Type:</span> <span className="capitalize">{app.application_type.replace('_', ' ')}</span></div>
                            {app.application_type === 'new_intake' ? (
                                <>
                                    <div><span className="font-semibold text-gray-500">Grade 7 School:</span> {app.grade7_school}</div>
                                    <div><span className="font-semibold text-gray-500">Grade 7 Results:</span> {app.grade7_results}</div>
                                </>
                            ) : (
                                <>
                                    <div><span className="font-semibold text-gray-500">Previous School:</span> {app.previous_school_name}</div>
                                    <div><span className="font-semibold text-gray-500">Current Form:</span> {app.current_form}</div>
                                    <div className="col-span-2"><span className="font-semibold text-gray-500">Transfer Reason:</span> {app.transfer_reason}</div>
                                </>
                            )}
                            <div className="col-span-2"><span className="font-semibold text-gray-500">Medical Information:</span> {app.medical_information || 'None'}</div>
                            <div className="col-span-2"><span className="font-semibold text-gray-500">Reason for Joining:</span> {app.reason_for_joining}</div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="documents">
                    <Card>
                        <CardHeader><CardTitle className="text-lg">Uploaded Documents</CardTitle></CardHeader>
                        <CardContent>
                            {app.documents && app.documents.length > 0 ? (
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {app.documents.map((doc: any) => (
                                        <div key={doc.id} className="border rounded-lg p-4 flex flex-col justify-between items-start hover:shadow-sm">
                                            <div className="flex items-center space-x-3 mb-4">
                                                <div className="p-2 bg-gray-100 rounded">
                                                    <FileText className="h-6 w-6 text-gray-600" />
                                                </div>
                                                <div>
                                                    <p className="font-medium capitalize text-sm">{doc.document_type.replace(/_/g, ' ')}</p>
                                                    <p className="text-xs text-gray-500">{doc.file_name}</p>
                                                </div>
                                            </div>
                                            <div className="flex w-full space-x-2">
                                                <Button size="sm" variant="outline" className="flex-1 text-xs h-8">
                                                    <Eye className="mr-1 h-3 w-3" /> View
                                                </Button>
                                                <Button size="sm" variant="outline" className="flex-1 text-xs h-8 text-green-600 hover:text-green-700">
                                                    <Check className="mr-1 h-3 w-3" /> Verify
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500">No documents uploaded.</p>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="status" className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle className="text-lg">Update Status</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                                <select 
                                    className="w-full h-10 px-3 py-2 border rounded-md bg-white text-sm"
                                    value={statusUpdate}
                                    onChange={(e) => setStatusUpdate(e.target.value)}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="documents_required">Documents Required</option>
                                    <option value="interview_scheduled">Interview Scheduled</option>
                                    <option value="waitlisted">Waitlisted</option>
                                    <option value="enrollment_pending">Enrollment Pending</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Remarks (Optional but required for rejections/docs)</label>
                                <Textarea 
                                    placeholder="Enter any remarks or reasons here..."
                                    value={remarks}
                                    onChange={(e) => setRemarks(e.target.value)}
                                    rows={3}
                                />
                            </div>
                            <Button onClick={handleUpdateStatus} className="bg-[#0B4619] hover:bg-[#135a24]">
                                <Save className="mr-2 h-4 w-4" /> Save Status
                            </Button>
                        </CardContent>
                    </Card>

                    <Card className="border-t-4 border-t-[#0B4619]">
                        <CardHeader><CardTitle className="text-lg">Final Decision</CardTitle></CardHeader>
                        <CardContent className="flex space-x-4">
                            <Button onClick={handleApprove} className="bg-green-600 hover:bg-green-700 text-white">
                                <CheckCircle className="mr-2 h-4 w-4" /> Approve Application
                            </Button>
                            <Button onClick={handleReject} variant="destructive">
                                <X className="mr-2 h-4 w-4" /> Reject Application
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default ApplicantDetailsPage;
