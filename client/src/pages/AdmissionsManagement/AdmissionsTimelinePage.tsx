import React, { useEffect, useState } from "react";
import { useRoute } from "wouter";
import { History, User, CheckCircle, Clock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// Note: This page could be embedded inside ApplicantDetailsPage or stand alone.
// We'll make it standalone for a specific application id, passed via route or props.
// For the route: /app/admissions/timeline/:id

const fetchTimeline = async (id: string) => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/applicants/${id}/timeline`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Failed to fetch timeline');
    return res.json();
};

const AdmissionsTimelinePage: React.FC<{ applicationId?: string }> = ({ applicationId }) => {
    const [, params] = useRoute("/app/admissions/timeline/:id");
    const id = applicationId || params?.id;

    const [logs, setLogs] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!id) return;
        fetchTimeline(id)
            .then(res => {
                setLogs(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    }, [id]);

    if (!id) return <div>Please provide an Application ID.</div>;
    if (loading) return <div className="animate-spin h-8 w-8 border-4 border-[#0B4619] border-t-transparent rounded-full mx-auto my-12"></div>;

    return (
        <Card className="shadow-sm border-gray-100">
            <CardHeader>
                <CardTitle className="text-lg flex items-center">
                    <History className="mr-2 h-5 w-5 text-[#0B4619]" />
                    Application History
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-gray-200 before:to-transparent">
                    {logs.length === 0 ? (
                        <p className="text-gray-500 text-center py-4 relative z-10">No history available for this application.</p>
                    ) : (
                        logs.map((log: any, index: number) => (
                            <div key={log.id} className={`relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active`}>
                                <div className="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-[#0B4619] text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 relative z-10">
                                    <CheckCircle className="w-5 h-5" />
                                </div>
                                <div className="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-lg border border-gray-200 bg-white shadow-sm">
                                    <div className="flex items-center justify-between space-x-2 mb-1">
                                        <div className="font-bold text-gray-900 capitalize">Status Changed to: {log.new_status.replace('_', ' ')}</div>
                                        <time className="text-xs font-medium text-[#D4AF37]">
                                            {new Date(log.changed_at).toLocaleString()}
                                        </time>
                                    </div>
                                    <div className="text-sm text-gray-500 mb-2">
                                        Previous: {log.old_status ? log.old_status.replace('_', ' ') : 'N/A'}
                                    </div>
                                    {log.remarks && (
                                        <div className="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-100">
                                            "{log.remarks}"
                                        </div>
                                    )}
                                    <div className="text-xs text-gray-400 mt-2 flex items-center">
                                        <User className="w-3 h-3 mr-1" /> By: {log.first_name ? `${log.first_name} ${log.last_name}` : 'System'}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </CardContent>
        </Card>
    );
};

export default AdmissionsTimelinePage;
