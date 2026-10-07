<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the application landing page.
     */
    public function index(): View
    {
        $categoriesCount = Schema::hasTable('product_categories')
            ? ProductCategory::where('is_active', true)->count()
            : 0;

        $activeListingsCount = Schema::hasTable('listings')
            ? Listing::where('status', 'ACTIVE')->count()
            : 0;

        $forumPostsCount = Schema::hasTable('forum_posts')
            ? ForumPost::where('status', 'ACTIVE')->count()
            : 0;

        return view('welcome', [
            'categoriesCount' => $categoriesCount,
            'activeListingsCount' => $activeListingsCount,
            'forumPostsCount' => $forumPostsCount,
        ]);
    }
}
