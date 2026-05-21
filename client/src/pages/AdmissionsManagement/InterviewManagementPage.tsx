import React, { useState } from "react";
import { Calendar, Clock, User, FileText, CheckCircle } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

// Note: This page would typically fetch a list of all interviews.
// For the sake of the requested design, we'll build a management layout.
// You could load all upcoming interviews here, or use this to schedule a new one.

const InterviewManagementPage: React.FC = () => {
    // State for scheduling a new interview
    const [appId, setAppId] = useState("");
    const [date, setDate] = useState("");
    const [time, setTime] = useState("");
    const [notes, setNotes] = useState("");

    const handleSchedule = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const token = localStorage.getItem('token');
            const res = await fetch('/api/app/admissions/interviews/schedule', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    application_id: appId,
                    interview_date: date,
                    interview_time: time,
                    notes: notes
                })
            });

            if (!res.ok) throw new Error("Failed to schedule");
            alert("Interview scheduled successfully!");
            setAppId(""); setDate(""); setTime(""); setNotes("");
        } catch (error) {
            console.error(error);
            alert("Error scheduling interview. Check application ID.");
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-3xl font-bold tracking-tight text-[#0B4619]">Interview Management</h2>
                <p className="text-gray-500">Schedule and manage applicant interviews.</p>
            </div>

            <div className="grid md:grid-cols-3 gap-6">
                {/* Schedule New Interview Form */}
                <Card className="md:col-span-1 shadow-sm border-gray-100">
                    <CardHeader>
                        <CardTitle className="text-lg text-[#0B4619]">Schedule Interview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSchedule} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Application ID</label>
                                <Input 
                                    required 
                                    type="number"
                                    placeholder="Enter Application ID" 
                                    value={appId}
                                    onChange={(e) => setAppId(e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <div className="relative">
                                    <Calendar className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                    <Input 
                                        required 
                                        type="date" 
                                        className="pl-9"
                                        value={date}
                                        onChange={(e) => setDate(e.target.value)}
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Time</label>
                                <div className="relative">
                                    <Clock className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                    <Input 
                                        required 
                                        type="time" 
                                        className="pl-9"
                                        value={time}
                                        onChange={(e) => setTime(e.target.value)}
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes / Instructions</label>
                                <Textarea 
                                    placeholder="Any notes for the interviewer or applicant..." 
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                />
                            </div>
                            <Button type="submit" className="w-full bg-[#0B4619] hover:bg-[#135a24]">
                                Schedule Interview
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Upcoming Interviews List (Placeholder for real data) */}
                <Card className="md:col-span-2 shadow-sm border-gray-100">
                    <CardHeader>
                        <CardTitle className="text-lg text-[#0B4619]">Upcoming Interviews</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col items-center justify-center py-12 text-gray-500">
                            <Calendar className="h-12 w-12 text-gray-300 mb-4" />
                            <p>Upcoming interviews will be listed here.</p>
                            <p className="text-sm">They are also visible on the dashboard.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default InterviewManagementPage;
