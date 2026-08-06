import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  BarChart3,
  BookOpen,
  DollarSign,
  GraduationCap,
  Users,
  FileText,
  Bell,
  Shield,
} from "lucide-react";
import { getLoginUrl } from "@/const";
import { useLocation } from "wouter";

export default function Home() {
  const { user, logout } = useAuth();
  const [, setLocation] = useLocation();

  if (user) {
    setLocation("/dashboard");
    return null;
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Navigation */}
      <nav className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-50">
        <div className="container flex justify-between items-center h-16">
          <div className="flex items-center gap-2">
            <div className="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center p-1">
              <img src="/logo-mark.png" alt="DestinyGate Institute" className="w-full h-full object-contain" />
            </div>
            <h1 className="text-xl font-bold text-foreground">DestinyGate</h1>
          </div>
          <Button
            onClick={() => {
              window.location.href = getLoginUrl();
            }}
            className="bg-primary hover:bg-primary/90"
          >
            Sign In
          </Button>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="py-20 px-4 bg-gradient-to-br from-primary/5 via-background to-secondary/5">
        <div className="container max-w-4xl mx-auto text-center">
          <h2 className="text-5xl md:text-6xl font-bold text-foreground mb-4">
            Comprehensive School Management System
          </h2>
          <p className="text-lg text-muted-foreground italic mb-6">
            "Raising a Godly, Skilled and Confident Generation"
          </p>
          <p className="text-xl text-muted-foreground mb-8 leading-relaxed">
            DestinyGate brings elegance and efficiency to every aspect of school operations. From student
            management to finance tracking, academics to behaviour records—all in one beautiful, unified platform.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button
              onClick={() => {
                window.location.href = getLoginUrl();
              }}
              size="lg"
              className="bg-primary hover:bg-primary/90 text-primary-foreground"
            >
              Get Started
            </Button>
            <Button size="lg" variant="outline">
              Learn More
            </Button>
          </div>
        </div>
      </section>

      {/* Features Grid */}
      <section className="py-20 px-4">
        <div className="container">
          <h3 className="text-3xl font-bold text-center mb-12">Core Features</h3>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {/* Student Management */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-secondary/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <Users className="text-secondary" size={24} />
                </div>
                <CardTitle className="text-lg">Student Management</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Complete student records, profiles, documents, and status tracking
                </p>
              </CardContent>
            </Card>

            {/* Finance */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-accent/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <DollarSign className="text-accent" size={24} />
                </div>
                <CardTitle className="text-lg">Finance & Bursar</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Fee management, payment tracking, receipts, and financial reports
                </p>
              </CardContent>
            </Card>

            {/* Academics */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-secondary/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <BookOpen className="text-secondary" size={24} />
                </div>
                <CardTitle className="text-lg">Academic Progress</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Marks recording, performance analysis, and teacher comments
                </p>
              </CardContent>
            </Card>

            {/* Attendance */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-accent/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <BarChart3 className="text-accent" size={24} />
                </div>
                <CardTitle className="text-lg">Attendance</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Daily attendance marking and comprehensive reporting
                </p>
              </CardContent>
            </Card>

            {/* Behaviour */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-secondary/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <Shield className="text-secondary" size={24} />
                </div>
                <CardTitle className="text-lg">Behaviour Tracking</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Issue recording, disciplinary actions, and parent meetings
                </p>
              </CardContent>
            </Card>

            {/* Notifications */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-accent/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <Bell className="text-accent" size={24} />
                </div>
                <CardTitle className="text-lg">Notifications</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Automated alerts for fees, attendance, and behaviour
                </p>
              </CardContent>
            </Card>

            {/* Reports */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-secondary/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <FileText className="text-secondary" size={24} />
                </div>
                <CardTitle className="text-lg">Reports & Export</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  PDF and Excel reports for all modules and data types
                </p>
              </CardContent>
            </Card>

            {/* Role-Based Access */}
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="bg-accent/10 w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                  <GraduationCap className="text-accent" size={24} />
                </div>
                <CardTitle className="text-lg">Role-Based Access</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  Secure portals for admin, staff, parents, and students
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* User Roles Section */}
      <section className="py-20 px-4 bg-card/50 border-t border-border">
        <div className="container">
          <h3 className="text-3xl font-bold text-center mb-12">Built for Everyone</h3>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[
              { role: "Admin", desc: "System management and configuration" },
              { role: "Headmaster", desc: "School oversight and reports" },
              { role: "Teachers", desc: "Class management and grading" },
              { role: "Bursar", desc: "Finance and payment tracking" },
              { role: "Staff", desc: "Role-specific operations" },
              { role: "Parents", desc: "Student progress monitoring" },
              { role: "Students", desc: "Personal academic tracking" },
            ].map((item, idx) => (
              <div key={idx} className="text-center">
                <div className="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                  <span className="text-2xl font-bold text-primary">{idx + 1}</span>
                </div>
                <h4 className="font-semibold text-foreground mb-2">{item.role}</h4>
                <p className="text-sm text-muted-foreground">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-20 px-4">
        <div className="container">
          <div className="grid md:grid-cols-4 gap-8 text-center">
            <div>
              <p className="text-4xl font-bold text-secondary mb-2">21</p>
              <p className="text-muted-foreground">Database Tables</p>
            </div>
            <div>
              <p className="text-4xl font-bold text-secondary mb-2">12</p>
              <p className="text-muted-foreground">Core Modules</p>
            </div>
            <div>
              <p className="text-4xl font-bold text-secondary mb-2">7</p>
              <p className="text-muted-foreground">User Roles</p>
            </div>
            <div>
              <p className="text-4xl font-bold text-secondary mb-2">100%</p>
              <p className="text-muted-foreground">Feature Complete</p>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 px-4 bg-gradient-to-r from-primary to-secondary text-primary-foreground">
        <div className="container text-center">
          <h3 className="text-3xl font-bold mb-6">Ready to Transform Your School?</h3>
          <p className="text-lg mb-8 opacity-90 max-w-2xl mx-auto">
            Experience the elegance and efficiency of DestinyGate. Sign in now to access all features and manage
            your school operations seamlessly.
          </p>
          <Button
            onClick={() => {
              window.location.href = getLoginUrl();
            }}
            size="lg"
            className="bg-primary-foreground text-primary hover:bg-primary-foreground/90"
          >
            Get Started Today
          </Button>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-border py-12 px-4 bg-card/50">
        <div className="container">
          <div className="grid md:grid-cols-4 gap-8 mb-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <div className="w-6 h-6 rounded bg-primary/10 border border-primary/20 flex items-center justify-center p-0.5">
                  <img src="/logo-mark.png" alt="DestinyGate Institute" className="w-full h-full object-contain" />
                </div>
                <span className="font-bold text-foreground">DestinyGate</span>
              </div>
              <p className="text-sm text-muted-foreground">
                Elegant school management for the modern institution
              </p>
              <p className="text-xs text-muted-foreground mt-3">
                15412 Samson Kanyemba Street, Runyararo West, Masvingo<br />
                0779 672 246 / 0710415364
              </p>
            </div>
            <div>
              <h4 className="font-semibold text-foreground mb-4">Product</h4>
              <ul className="space-y-2 text-sm text-muted-foreground">
                <li>Features</li>
                <li>Pricing</li>
                <li>Security</li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-foreground mb-4">Company</h4>
              <ul className="space-y-2 text-sm text-muted-foreground">
                <li>About</li>
                <li>Blog</li>
                <li>Contact</li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-foreground mb-4">Legal</h4>
              <ul className="space-y-2 text-sm text-muted-foreground">
                <li>Privacy</li>
                <li>Terms</li>
                <li>Security</li>
              </ul>
            </div>
          </div>
          <div className="border-t border-border pt-8 text-center text-sm text-muted-foreground">
            <p>&copy; 2026 DestinyGate Institute Management System. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
