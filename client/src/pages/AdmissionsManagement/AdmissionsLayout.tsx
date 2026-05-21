import React from "react";
import { Link, useLocation } from "wouter";
import { LayoutDashboard, Users, Calendar, FolderOpen, History, LogOut } from "lucide-react";
import { useAuth } from "@/_core/hooks/useAuth";

const AdmissionsLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [location] = useLocation();
  const { logout, user } = useAuth();

  const navigation = [
    { name: "Dashboard", href: "/app/admissions/dashboard", icon: LayoutDashboard },
    { name: "Applicants", href: "/app/admissions/applicants", icon: Users },
    { name: "Interviews", href: "/app/admissions/interviews", icon: Calendar },
    { name: "Offers", href: "/app/admissions/offers", icon: FolderOpen },
    { name: "Enrollment", href: "/app/admissions/enrollment", icon: History },
  ];

  return (
    <div className="flex h-screen bg-gray-50 font-sans">
      {/* Sidebar */}
      <aside className="w-64 bg-[#0B4619] text-white flex flex-col shadow-lg transition-all duration-300">
        <div className="p-6 flex items-center justify-center border-b border-[#135a24]">
          <h1 className="text-2xl font-bold tracking-tight text-[#D4AF37]">DestinyGate</h1>
        </div>
        
        <div className="p-4 border-b border-[#135a24]">
          <p className="font-medium truncate">{user?.name}</p>

        </div>

        <nav className="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
          {navigation.map((item) => {
            const isActive = location === item.href || location.startsWith(item.href + '/');
            return (
              <Link key={item.name} href={item.href}>
                <a className={`flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200 ${
                  isActive 
                    ? "bg-[#D4AF37] text-[#0B4619] shadow-md" 
                    : "text-gray-200 hover:bg-[#135a24] hover:text-white"
                }`}>
                  <item.icon className={`mr-3 h-5 w-5 ${isActive ? "text-[#0B4619]" : "text-gray-300"}`} />
                  {item.name}
                </a>
              </Link>
            );
          })}
        </nav>

        <div className="p-4 border-t border-[#135a24]">
          <button 
            onClick={() => logout()}
            className="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-200 rounded-lg hover:bg-[#135a24] hover:text-white transition-colors duration-200"
          >
            <LogOut className="mr-3 h-5 w-5 text-gray-300" />
            Logout
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 overflow-y-auto bg-white">
        <div className="max-w-7xl mx-auto py-8 px-8">
          {children}
        </div>
      </main>
    </div>
  );
};

export default AdmissionsLayout;
