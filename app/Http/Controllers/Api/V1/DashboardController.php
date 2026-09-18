<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): array
    {
        // One round trip for counters; model scopes still exclude soft-deleted rows.
        $counts = DB::query()
            ->selectSub(Project::selectRaw('COUNT(*)'), 'projects')
            ->selectSub(Project::where('is_featured', true)->selectRaw('COUNT(*)'), 'featured')
            ->selectSub(Article::where('published_at', '<=', now())->selectRaw('COUNT(*)'), 'published')
            ->selectSub(Skill::selectRaw('COUNT(*)'), 'skills')
            ->selectSub(ContactMessage::selectRaw('COUNT(*)'), 'messages')
            ->selectSub(ContactMessage::where('status', 'unread')->selectRaw('COUNT(*)'), 'unread')
            ->first();

        return ['data' => [
            'counts' => [
                'Projects' => (int) $counts->projects,
                'Featured projects' => (int) $counts->featured,
                'Published blog posts' => (int) $counts->published,
                'Skills' => (int) $counts->skills,
                'Contact messages' => (int) $counts->messages,
                'Unread messages' => (int) $counts->unread,
            ],
            'messages' => ContactMessage::latest()->limit(5)->get(['id', 'name', 'subject', 'status', 'created_at']),
            'projects' => Project::latest('updated_at')->limit(5)->get(['id', 'title', 'slug', 'updated_at', 'published_at']),
        ]];
    }
}
