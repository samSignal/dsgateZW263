import React, { useEffect, useState } from "react";
import { Link } from "wouter";
import { Search, Filter, MoreHorizontal, ChevronLeft, ChevronRight, Eye } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

const fetchApplicants = async (page = 1, search = '', status = 'all') => {
    const token = localStorage.getItem('token');
    const query = new URLSearchParams({
        page: page.toString(),
        search,
        status
    });

    const res = await fetch(`/api/app/admissions/applicants?${query.toString()}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    if (!res.ok) throw new Error('Failed to fetch applicants');
    return res.json();
};

const statusColors: Record<string, string> = {
    draft: "bg-gray-100 text-gray-800",
    submitted: "bg-blue-100 text-blue-800",
    under_review: "bg-purple-100 text-purple-800",
    interview_scheduled: "bg-yellow-100 text-yellow-800",
    documents_required: "bg-orange-100 text-orange-800",
    accepted: "bg-green-100 text-green-800",
    rejected: "bg-red-100 text-red-800",
    waitlisted: "bg-gray-100 text-gray-800",
    enrollment_pending: "bg-indigo-100 text-indigo-800",
    enrolled: "bg-[#0B4619] text-white",
};

const ApplicantsListPage: React.FC = () => {
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [page, setPage] = useState(1);

    const loadData = () => {
        setLoading(true);
        fetchApplicants(page, search, statusFilter)
            .then(res => {
                setData(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    };

    useEffect(() => {
        loadData();
    }, [page, statusFilter]); // Don't trigger on search keystrokes, wait for Enter/Search button

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        setPage(1);
        loadData();
    };

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight text-[#0B4619]">Applicants List</h2>
                    <p className="text-gray-500">Manage and review student applications.</p>
                </div>
            </div>

            <Card className="shadow-sm border-gray-100">
                <CardHeader className="pb-4">
                    <form onSubmit={handleSearch} className="flex space-x-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input 
                                placeholder="Search by name, application number, guardian phone..." 
                                className="pl-9 bg-gray-50"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div className="w-48">
                            <select 
                                className="w-full h-10 px-3 py-2 border rounded-md bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#0B4619]"
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    setPage(1);
                                }}
                            >
                                <option value="all">All Statuses</option>
                                <option value="submitted">Submitted</option>
                                <option value="under_review">Under Review</option>
                                <option value="interview_scheduled">Interview Scheduled</option>
                                <option value="documents_required">Documents Required</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <Button type="submit" className="bg-[#0B4619] hover:bg-[#135a24]">
                            Search
                        </Button>
                    </form>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="flex justify-center py-12">
                            <div className="animate-spin h-8 w-8 border-4 border-[#0B4619] border-t-transparent rounded-full"></div>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-y">
                                    <tr>
                                        <th scope="col" className="px-6 py-3">App Number</th>
                                        <th scope="col" className="px-6 py-3">Student Name</th>
                                        <th scope="col" className="px-6 py-3">Guardian Name</th>
                                        <th scope="col" className="px-6 py-3">Phone</th>
                                        <th scope="col" className="px-6 py-3">Status</th>
                                        <th scope="col" className="px-6 py-3">Submitted</th>
                                        <th scope="col" className="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data?.data?.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-6 py-12 text-center text-gray-500">
                                                No applicants found matching the criteria.
                                            </td>
                                        </tr>
                                    ) : (
                                        data?.data?.map((app: any) => (
                                            <tr key={app.id} className="bg-white border-b hover:bg-gray-50 transition-colors">
                                                <td className="px-6 py-4 font-medium text-gray-900">
                                                    {app.application_number}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {app.student_first_name} {app.student_last_name}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {app.guardian_name || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {app.guardian_phone || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${statusColors[app.status] || 'bg-gray-100 text-gray-800'}`}>
                                                        {app.status.replace('_', ' ')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link href={`/app/admissions/applicants/${app.id}`}>
                                                        <a className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0B4619]">
                                                            <Eye className="mr-1.5 h-3.5 w-3.5" /> View
                                                        </a>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                            
                            {/* Pagination */}
                            {data && data.last_page > 1 && (
                                <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-b-lg">
                                    <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing <span className="font-medium">{data.from || 0}</span> to <span className="font-medium">{data.to || 0}</span> of{' '}
                                                <span className="font-medium">{data.total}</span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                                <button
                                                    onClick={() => setPage(Math.max(1, page - 1))}
                                                    disabled={page === 1}
                                                    className="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                                                >
                                                    <span className="sr-only">Previous</span>
                                                    <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                                                </button>
                                                
                                                <button
                                                    onClick={() => setPage(Math.min(data.last_page, page + 1))}
                                                    disabled={page === data.last_page}
                                                    className="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                                                >
                                                    <span className="sr-only">Next</span>
                                                    <ChevronRight className="h-5 w-5" aria-hidden="true" />
                                                </button>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default ApplicantsListPage;
