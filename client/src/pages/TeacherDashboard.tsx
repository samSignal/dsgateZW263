import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { BookOpen, Users, BarChart3, MessageSquare, LogOut } from "lucide-react";
import { useState } from "react";
import { trpc } from "@/lib/trpc";
import { toast } from "sonner";

export default function TeacherDashboard() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState("classes");
  const [marksData, setMarksData] = useState({
    studentId: "",
    classId: "",
    subjectId: "",
    marks: "",
    totalMarks: "100",
    assessmentType: "exam",
  });
  const [commentData, setCommentData] = useState({
    studentId: "",
    classId: "",
    progress: "",
    participation: "",
    homework: "",
    behaviour: "",
    areasForImprovement: "",
  });

  const recordMarks = trpc.academics.recordMarks.useMutation({
    onSuccess: () => {
      toast.success("Marks recorded successfully");
      setMarksData({ studentId: "", classId: "", subjectId: "", marks: "", totalMarks: "100", assessmentType: "exam" });
    },
    onError: (error: unknown) => {
      toast.error((error as any)?.message || "Failed to record marks");
    },
  });

  const addComment = trpc.academics.addTeacherComment.useMutation({
    onSuccess: () => {
      toast.success("Comment added successfully");
      setCommentData({ studentId: "", classId: "", progress: "", participation: "", homework: "", behaviour: "", areasForImprovement: "" });
    },
    onError: (error: unknown) => {
      toast.error((error as any)?.message || "Failed to add comment");
    },
  });

  const handleRecordMarks = (e: React.FormEvent) => {
    e.preventDefault();
    if (!marksData.studentId || !marksData.marks) {
      toast.error("Please fill in required fields");
      return;
    }
    recordMarks.mutate({
      studentId: parseInt(marksData.studentId),
      classId: parseInt(marksData.classId),
      subjectId: parseInt(marksData.subjectId),
      marks: marksData.marks,
      totalMarks: marksData.totalMarks,
      assessmentType: marksData.assessmentType as any,
      academicYear: new Date().getFullYear().toString(),
      term: "term1",
    });
  };

  const handleAddComment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!commentData.studentId) {
      toast.error("Please select a student");
      return;
    }
    addComment.mutate({
      studentId: parseInt(commentData.studentId),
      classId: parseInt(commentData.classId),
      progress: commentData.progress,
      participation: commentData.participation,
      homework: commentData.homework,
      behaviour: commentData.behaviour,
      areasForImprovement: commentData.areasForImprovement,
      academicYear: new Date().getFullYear().toString(),
      term: "term1",
    });
  };

  return (
    <div className="min-h-screen bg-background">
      <div className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-40">
        <div className="container flex justify-between items-center h-16">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Teacher Dashboard</h1>
            <p className="text-sm text-muted-foreground">Academic Management</p>
          </div>
          <div className="flex gap-2">
            <Button
              onClick={() => {
                window.location.href = "/teacher/results-native";
              }}
              variant="outline"
            >
              Stream-Native Results
            </Button>
            <Button onClick={() => logout()} variant="outline" className="gap-2">
              <LogOut size={16} />
              Logout
            </Button>
          </div>
        </div>
      </div>

      <div className="container py-8">
        {/* Key Metrics */}
        <div className="grid md:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Users size={16} className="text-primary" />
                My Classes
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <BookOpen size={16} className="text-secondary" />
                Students
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <BarChart3 size={16} className="text-accent" />
                Marks Recorded
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <MessageSquare size={16} className="text-green-600" />
                Comments
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
            </CardContent>
          </Card>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="classes">Classes</TabsTrigger>
            <TabsTrigger value="marks">Record Marks</TabsTrigger>
            <TabsTrigger value="comments">Comments</TabsTrigger>
            <TabsTrigger value="attendance">Attendance</TabsTrigger>
          </TabsList>

          {/* Classes Tab */}
          <TabsContent value="classes" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>My Classes</CardTitle>
                <CardDescription>Classes assigned to you</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>No classes assigned</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Marks Tab */}
          <TabsContent value="marks" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Record Student Marks</CardTitle>
                <CardDescription>Enter marks for students</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleRecordMarks} className="space-y-4">
                  <div className="grid md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Student</Label>
                      <Select value={marksData.studentId} onValueChange={(value) => setMarksData({ ...marksData, studentId: value })}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select student" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="1">John Doe</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Class</Label>
                      <Select value={marksData.classId} onValueChange={(value) => setMarksData({ ...marksData, classId: value })}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select class" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="1">Form 1A</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Subject</Label>
                      <Select value={marksData.subjectId} onValueChange={(value) => setMarksData({ ...marksData, subjectId: value })}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select subject" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="1">Mathematics</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Assessment Type</Label>
                      <Select value={marksData.assessmentType} onValueChange={(value) => setMarksData({ ...marksData, assessmentType: value })}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="exam">Exam</SelectItem>
                          <SelectItem value="assignment">Assignment</SelectItem>
                          <SelectItem value="weekly_test">Weekly Test</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Marks Obtained</Label>
                      <Input type="number" placeholder="85" value={marksData.marks} onChange={(e) => setMarksData({ ...marksData, marks: e.target.value })} />
                    </div>
                    <div className="space-y-2">
                      <Label>Total Marks</Label>
                      <Input type="number" placeholder="100" value={marksData.totalMarks} onChange={(e) => setMarksData({ ...marksData, totalMarks: e.target.value })} />
                    </div>
                  </div>
                  <Button type="submit" className="bg-primary hover:bg-primary/90" disabled={recordMarks.isPending}>
                    {recordMarks.isPending ? "Recording..." : "Record Marks"}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Comments Tab */}
          <TabsContent value="comments" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Add Student Comment</CardTitle>
                <CardDescription>Provide feedback on student performance</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleAddComment} className="space-y-4">
                  <div className="grid md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Student</Label>
                      <Select value={commentData.studentId} onValueChange={(value) => setCommentData({ ...commentData, studentId: value })}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select student" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="1">John Doe</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Class</Label>
                      <Select value={commentData.classId} onValueChange={(value) => setCommentData({ ...commentData, classId: value })}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select class" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="1">Form 1A</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Progress</Label>
                    <Textarea placeholder="Comment on student's academic progress" value={commentData.progress} onChange={(e) => setCommentData({ ...commentData, progress: e.target.value })} />
                  </div>
                  <div className="space-y-2">
                    <Label>Participation</Label>
                    <Textarea placeholder="Comment on class participation" value={commentData.participation} onChange={(e) => setCommentData({ ...commentData, participation: e.target.value })} />
                  </div>
                  <div className="space-y-2">
                    <Label>Homework & Assignments</Label>
                    <Textarea placeholder="Comment on homework completion" value={commentData.homework} onChange={(e) => setCommentData({ ...commentData, homework: e.target.value })} />
                  </div>
                  <div className="space-y-2">
                    <Label>Behaviour</Label>
                    <Textarea placeholder="Comment on classroom behaviour" value={commentData.behaviour} onChange={(e) => setCommentData({ ...commentData, behaviour: e.target.value })} />
                  </div>
                  <div className="space-y-2">
                    <Label>Areas for Improvement</Label>
                    <Textarea placeholder="Suggest areas for improvement" value={commentData.areasForImprovement} onChange={(e) => setCommentData({ ...commentData, areasForImprovement: e.target.value })} />
                  </div>
                  <Button type="submit" className="bg-primary hover:bg-primary/90" disabled={addComment.isPending}>
                    {addComment.isPending ? "Adding..." : "Add Comment"}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Attendance Tab */}
          <TabsContent value="attendance" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Mark Attendance</CardTitle>
                <CardDescription>Record daily class attendance</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>Select a class to mark attendance</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
