<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\Skill;

class DashboardController extends Controller
{
    public function __invoke(): array
    {
        return ['data' => [
            'counts' => ['Projects' => Project::count(), 'Featured projects' => Project::where('is_featured', true)->count(), 'Published blog posts' => Article::where('published_at', '<=', now())->count(), 'Skills' => Skill::count(), 'Contact messages' => ContactMessage::count(), 'Unread messages' => ContactMessage::where('status', 'unread')->count()],
            'messages' => ContactMessage::latest()->limit(5)->get(),
            'projects' => Project::latest('updated_at')->limit(5)->get(['id', 'title', 'slug', 'updated_at', 'published_at']),
        ]];
    }
}
