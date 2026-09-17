<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use App\Models\Education;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\Technology;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::firstOrCreate(['key' => 'site'], ['value' => [
            'title' => 'Chimeng Ly | Software Developer',
            'description' => 'Computer Science graduate focused on backend systems, REST APIs, full-stack and mobile development.',
            'footer' => 'Backend. Full Stack. Mobile.',
        ], 'is_public' => true]);
        Profile::firstOrCreate(['slug' => 'chimeng-ly'], [
            'name' => 'Chimeng Ly', 'headline' => 'Computer Science Graduate | Backend & Full-Stack Developer',
            'introduction' => 'I build backend systems, APIs, full-stack applications, and mobile applications. I am interested in how database design and cloud infrastructure turn practical ideas into useful software.',
            'biography' => 'I am a Computer Science graduate from Paragon International University. I enjoy building software that solves practical problems, from connected mobile applications to backend services and distributed systems.',
            'availability' => 'Available for Backend, Full-Stack, Mobile, and Software Engineering opportunities.',
            'focus' => "- Backend development and REST APIs\n- Full-stack and mobile systems\n- Database and distributed systems\n- Cloud computing, system design, and DevOps",
            'learning' => "- Spring Boot\n- Docker and Kubernetes\n- System design\n- Cloud and backend architecture\n- API design",
            'philosophy' => 'I value clean code, maintainability, security, scalability, and usability. My goal is to understand the problem first, choose sensible tools, and build software that people can rely on.',
        ]);
        $categories = [
            'Backend' => ['Laravel', 'PHP', 'Java', 'Spring Boot', 'Django', 'Python', 'REST API', 'Golang'],
            'Frontend' => ['Next.js', 'React', 'TypeScript', 'JavaScript', 'HTML', 'CSS', 'Tailwind CSS'],
            'Mobile' => ['Flutter', 'Dart', 'React Native'],
            'Database' => ['PostgreSQL', 'MySQL', 'Supabase'],
            'DevOps & Cloud' => ['Docker', 'Kubernetes', 'DigitalOcean', 'Vercel', 'Cloudflare', 'Linux', 'GitHub Actions'],
            'Development Tools' => ['Git', 'GitHub', 'Postman', 'IntelliJ IDEA', 'VS Code', 'Figma'],
        ];
        foreach ($categories as $name => $skills) {
            $category = SkillCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => array_search($name, array_keys($categories))]);
            foreach ($skills as $order => $name) {
                Skill::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'skill_category_id' => $category->id, 'sort_order' => $order, 'is_visible' => true, 'proficiency' => in_array($name, ['Spring Boot', 'Docker', 'Kubernetes']) ? 'Currently learning' : null]);
                Technology::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            }
        }
        foreach (['Backend', 'Full Stack', 'Mobile', 'AI / Computer Vision', 'DevOps', 'Cloud', 'Database', 'Distributed Systems'] as $name) {
            ProjectCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
        foreach (['Backend', 'Laravel', 'Java', 'Spring Boot', 'PostgreSQL', 'Docker', 'Kubernetes', 'Cloud', 'API', 'System Design', 'Computer Science', 'Backend Architecture', 'Database Design', 'Distributed Systems', 'Security'] as $name) {
            ArticleCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
        Education::firstOrCreate(['institution' => 'Paragon International University', 'qualification' => 'Bachelor of Computer Science'], ['field_of_study' => 'Computer Science', 'published_at' => now()]);
        $projects = [
            ['tamdan-kon', 'Tamdan-Kon School Bus Tracking System', 'A real-time school bus tracking and student attendance system designed for parents, drivers, and school administrators.', "- Live bus GPS tracking and route monitoring\n- ETA\n- RFID student attendance\n- Boarding and arrival notifications\n- Emergency alerts\n- Driver route controls\n- Parent-driver-admin communication\n- Admin monitoring, users, and routes"],
            ['phone-shop', 'Phone Shop Management / E-Commerce System', 'A phone shop management and e-commerce project.', null],
            ['aba-payment-bot', 'ABA Payment Calculator Telegram Bot', 'A Telegram bot that analyzes ABA payment messages and calculates transaction totals over selected periods.', "- Payment message parsing\n- Daily, 3-day, weekly, and monthly totals\n- Merchant information extraction\n- PostgreSQL/Supabase storage\n- Secure environment configuration"],
            ['hand-gesture-recognition', 'Hand Gesture Recognition System', 'A computer vision project for hand tracking and gesture recognition.', "- Webcam hand detection\n- Two-hand tracking\n- Finger counting\n- Gesture classification\n- Training dataset\n- Real-time prediction\n- Desktop UI"],
            ['distributed-database', 'Distributed Database System', 'Exploring PostgreSQL, Citus, sharding, and distributed queries.', "- Coordinator and worker nodes\n- Sharding\n- Distributed queries\n- Horizontal scaling concepts"],
            ['distributed-storage', 'Distributed CDN / Storage System', 'A Golang project exploring communication and file distribution across storage nodes.', "- Central server\n- Multiple storage nodes\n- Geographic storage concept\n- File distribution\n- Node communication"],
            ['infrastructure-learning', 'Docker / Kubernetes Learning Projects', 'Infrastructure practice with containers, PostgreSQL, and multi-container deployments.', null],
        ];
        foreach ($projects as [$slug,$title,$summary,$features]) {
            Project::firstOrCreate(['slug' => $slug], ['title' => $title, 'summary' => $summary, 'key_features' => $features]);
        }
    }
}
