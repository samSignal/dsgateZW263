import React, { useEffect, useState } from "react";
import { Users, FileText, CheckCircle, Clock, XCircle, AlertCircle } from "lucide-react";
import { getApiUrl } from "@/_core/hooks/useAuth"; // Fallback to window/env
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// Assuming we have a standard fetch call since we don't have a direct API client imported
const fetchDashboardStats = async () => {
    const token = localStorage.getItem('token');
    const res = await fetch(`/api/app/admissions/dashboard`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    if (!res.ok) throw new Error('Failed to fetch stats');
    return res.json();
};

const AdmissionsDashboardPage: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardStats()
      .then(res => {
          setData(res.data);
          setLoading(false);
      })
      .catch(err => {
          console.error(err);
          setLoading(false);
      });
  }, []);

  if (loading) {
      return <div className="flex h-full items-center justify-center"><div className="animate-spin h-8 w-8 border-4 border-[#0B4619] border-t-transparent rounded-full"></div></div>;
  }

  const kpis = data?.kpis || {};
  const recentApplications = data?.recent_applications || [];
  const upcomingInterviews = data?.upcoming_interviews || [];

  const statCards = [
    { title: "Total Applications", value: kpis.total || 0, icon: FileText, color: "text-blue-600", bg: "bg-blue-100" },
    { title: "Submitted", value: kpis.submitted || 0, icon: CheckCircle, color: "text-[#D4AF37]", bg: "bg-yellow-100" },
    { title: "Under Review", value: kpis.under_review || 0, icon: Clock, color: "text-purple-600", bg: "bg-purple-100" },
    { title: "Accepted", value: kpis.accepted || 0, icon: CheckCircle, color: "text-[#0B4619]", bg: "bg-green-100" },
    { title: "Rejected", value: kpis.rejected || 0, icon: XCircle, color: "text-red-600", bg: "bg-red-100" },
    { title: "Enrollment Pending", value: kpis.enrollment_pending || 0, icon: AlertCircle, color: "text-orange-600", bg: "bg-orange-100" }
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-[#0B4619]">Dashboard Overview</h2>
        <p className="text-gray-500">Welcome to the Admissions Management System.</p>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {statCards.map((stat, index) => (
          <Card key={index} className="shadow-sm border-gray-100 hover:shadow-md transition-shadow">
            <CardContent className="p-6 flex items-center space-x-4">
              <div className={`p-4 rounded-full ${stat.bg}`}>
                <stat.icon className={`h-8 w-8 ${stat.color}`} />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500">{stat.title}</p>
                <h3 className="text-3xl font-bold text-gray-900">{stat.value}</h3>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Recent Applications */}
        <Card className="shadow-sm border-gray-100">
          <CardHeader>
            <CardTitle className="text-lg text-[#0B4619]">Recent Applications</CardTitle>
          </CardHeader>
          <CardContent>
            {recentApplications.length === 0 ? (
                <p className="text-gray-500 text-sm">No recent applications found.</p>
            ) : (
                <div className="space-y-4">
                {recentApplications.map((app: any) => (
                    <div key={app.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                        <div>
                            <p className="font-medium text-gray-900">{app.student_first_name} {app.student_last_name}</p>
                            <p className="text-sm text-gray-500">{app.application_number} • {app.status}</p>
                        </div>
                        <div className="text-sm text-gray-400">
                            {new Date(app.created_at).toLocaleDateString()}
                        </div>
                    </div>
                ))}
                </div>
            )}
          </CardContent>
        </Card>

        {/* Upcoming Interviews */}
        <Card className="shadow-sm border-gray-100">
          <CardHeader>
            <CardTitle className="text-lg text-[#0B4619]">Upcoming Interviews</CardTitle>
          </CardHeader>
          <CardContent>
            {upcomingInterviews.length === 0 ? (
                <p className="text-gray-500 text-sm">No upcoming interviews scheduled.</p>
            ) : (
                <div className="space-y-4">
                {upcomingInterviews.map((interview: any) => (
                    <div key={interview.id} className="flex items-center justify-between p-4 border rounded-lg border-l-4 border-l-[#D4AF37] hover:bg-gray-50">
                        <div>
                            <p className="font-medium text-gray-900">{interview.student_first_name} {interview.student_last_name}</p>
                            <p className="text-sm text-gray-500">{interview.interview_date} at {interview.interview_time}</p>
                        </div>
                        <div className="text-xs font-semibold px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                            {interview.status}
                        </div>
                    </div>
                ))}
                </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default AdmissionsDashboardPage;
