<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

// Public News Explorer Routes
Route::get('/', [NewsController::class, 'index'])->name('news.index');
Route::redirect('/world-cup-news', '/');
Route::get('/world-cup-news/top-stories', [NewsController::class, 'topStories'])->name('news.top');
Route::get('/world-cup-news/trending', [NewsController::class, 'trending'])->name('news.trending');
Route::get('/world-cup-news/fifa', [NewsController::class, 'fifa'])->name('news.fifa');
Route::get('/world-cup-news/ai-news', [NewsController::class, 'aiNews'])->name('news.ai');
Route::get('/world-cup-news/fixtures', [NewsController::class, 'fixtures'])->name('news.fixtures');
Route::get('/world-cup-news/live-score', [NewsController::class, 'scores'])->name('news.scores');
Route::get('/world-cup-news/gallery', [NewsController::class, 'gallery'])->name('news.gallery');
Route::post('/world-cup-news/scoreboard/refresh', [NewsController::class, 'refreshScoreboard'])->name('news.scoreboard.refresh');
Route::get('/media/fifa-placeholder/{seed}.svg', [NewsController::class, 'placeholderImage'])->name('media.placeholder');
Route::get('/media/news-image/{article}', [NewsController::class, 'articleImage'])->name('media.news-image');
Route::get('/news/{article}/visit', [NewsController::class, 'trackArticleClick'])->name('news.visit');
Route::post('/analytics/visitor-context', [NewsController::class, 'updateVisitorContext'])->name('analytics.visitor-context');
Route::get('/api/section/{section}/more', [NewsController::class, 'sectionMoreArticles'])->name('news.section.more');

// Admin Auth Routes
Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::any('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// Admin Dashboard Routes
Route::middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
    Route::get('/admin/analytics/ranking', [AdminController::class, 'rankingAnalytics'])->name('admin.analytics.ranking');
    Route::get('/admin/trends', [AdminController::class, 'trends'])->name('admin.trends');
    Route::get('/admin/trends/sync-status', [AdminController::class, 'trendsSyncStatus'])->name('admin.trends.sync-status');
    Route::get('/admin/destroy', [AdminController::class, 'destroyPage'])->name('admin.destroy');
    Route::get('/admin/promotions', [AdminController::class, 'promotions'])->name('admin.promotions');
    Route::get('/admin/sync-status', [AdminController::class, 'syncStatus'])->name('admin.sync-status');
    
    // Section Management
    Route::post('/admin/sections', [AdminController::class, 'storeSection'])->name('admin.sections.store');
    Route::post('/admin/sections/{section}/toggle', [AdminController::class, 'toggleSection'])->name('admin.sections.toggle');
    Route::post('/admin/sections/{section}/default', [AdminController::class, 'setDefaultSection'])->name('admin.sections.default');
    Route::delete('/admin/sections/{section}', [AdminController::class, 'deleteSection'])->name('admin.sections.delete');

    // Topic Management
    Route::post('/admin/topics', [AdminController::class, 'storeTopic'])->name('admin.topics.store');
    Route::post('/admin/topics/{topic}/toggle', [AdminController::class, 'toggleTopic'])->name('admin.topics.toggle');
    Route::delete('/admin/topics/{topic}', [AdminController::class, 'deleteTopic'])->name('admin.topics.delete');
    
    // Article Management
    Route::post('/admin/articles/{article}/toggle-visibility', [AdminController::class, 'toggleArticleVisibility'])->name('admin.articles.toggle-visibility');
    Route::post('/admin/articles/{article}/toggle-featured', [AdminController::class, 'toggleArticleFeatured'])->name('admin.articles.toggle-featured');
    Route::delete('/admin/articles/{article}', [AdminController::class, 'deleteArticle'])->name('admin.articles.delete');
    Route::post('/admin/articles/bulk-delete', [AdminController::class, 'bulkDeleteArticles'])->name('admin.articles.bulk-delete');
    
    // Profile & Credentials
    Route::post('/admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/promotions', [AdminController::class, 'updatePromotions'])->name('admin.promotions.update');
    Route::post('/admin/trends/refresh', [AdminController::class, 'refreshTrends'])->name('admin.trends.refresh');
    Route::post('/admin/trends/restart', [AdminController::class, 'stopAndResyncTrends'])->name('admin.trends.restart');

    // Manual Fetch
    Route::post('/admin/fetch-news', [AdminController::class, 'fetchNewsNow'])->name('admin.fetch-news');
    Route::post('/admin/fetch-news/restart', [AdminController::class, 'stopAndResync'])->name('admin.fetch-news.restart');
});
