<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KeralaLotteryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\GoldRateController;
use Illuminate\Support\Facades\Route;

// Public News Explorer Routes
Route::get('/', [NewsController::class, 'index'])->name('news.index');
Route::redirect('/world-cup-news', '/');
Route::get('/news/article/{article:slug}', [NewsController::class, 'showArticle'])->name('news.article');
Route::get('/trends/{slug}', [NewsController::class, 'trendPage'])->name('news.trend-page');
Route::get('/kerala-lottery', [KeralaLotteryController::class, 'index'])->name('kerala-lottery.index');
Route::get('/kerala-lottery/today', [KeralaLotteryController::class, 'today'])->name('kerala-lottery.today');
Route::get('/kerala-lottery/{result:slug}/view-pdf', [KeralaLotteryController::class, 'viewPdf'])->name('kerala-lottery.pdf.view');
Route::get('/kerala-lottery/{result:slug}/download-pdf', [KeralaLotteryController::class, 'downloadPdf'])->name('kerala-lottery.pdf.download');
Route::get('/kerala-lottery/{result:slug}', [KeralaLotteryController::class, 'show'])->name('kerala-lottery.show');

// Public Gold Rate Routes
Route::get('/gold-rate-today', [GoldRateController::class, 'index'])->name('news.gold-rate.index');
Route::get('/gold-rate/{city}', [GoldRateController::class, 'show'])->name('news.gold-rate');

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

// Static Informational Pages
Route::get('/about-us', [NewsController::class, 'staticPage'])->defaults('page', 'about-us')->name('pages.about');
Route::get('/contact-us', [NewsController::class, 'staticPage'])->defaults('page', 'contact-us')->name('pages.contact');
Route::get('/privacy-policy', [NewsController::class, 'staticPage'])->defaults('page', 'privacy-policy')->name('pages.privacy');
Route::get('/terms', [NewsController::class, 'staticPage'])->defaults('page', 'terms')->name('pages.terms');
Route::get('/disclaimer', [NewsController::class, 'staticPage'])->defaults('page', 'disclaimer')->name('pages.disclaimer');
Route::get('/affiliate-disclosure', [NewsController::class, 'staticPage'])->defaults('page', 'affiliate-disclosure')->name('pages.affiliate');

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
    Route::post('/admin/destroy/run', [AdminController::class, 'runDestroyProcess'])->name('admin.destroy.run');
    Route::post('/admin/destroy/settings', [AdminController::class, 'saveDestroySettings'])->name('admin.destroy.settings');
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
    Route::post('/admin/articles/{article}/toggle-favorite', [AdminController::class, 'toggleArticleFavorite'])->name('admin.articles.toggle-favorite');
    Route::delete('/admin/articles/{article}', [AdminController::class, 'deleteArticle'])->name('admin.articles.delete');
    Route::post('/admin/articles/bulk-delete', [AdminController::class, 'bulkDeleteArticles'])->name('admin.articles.bulk-delete');
    
    // Profile & Credentials
    Route::post('/admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/promotions', [AdminController::class, 'updatePromotions'])->name('admin.promotions.update');
    Route::post('/admin/trends/refresh', [AdminController::class, 'refreshTrends'])->name('admin.trends.refresh');
    Route::post('/admin/trends/cleanup', [AdminController::class, 'cleanupTrendKeywords'])->name('admin.trends.cleanup');
    Route::post('/admin/trends/restart', [AdminController::class, 'stopAndResyncTrends'])->name('admin.trends.restart');

    // Manual Fetch
    Route::post('/admin/fetch-news', [AdminController::class, 'fetchNewsNow'])->name('admin.fetch-news');
    Route::post('/admin/fetch-news/restart', [AdminController::class, 'stopAndResync'])->name('admin.fetch-news.restart');

    // Lottery Admin
    Route::post('/admin/lottery/sync', [KeralaLotteryController::class, 'adminSync'])->name('admin.lottery.sync');
    Route::post('/admin/lottery/backfill', [KeralaLotteryController::class, 'adminBackfill'])->name('admin.lottery.backfill');
    Route::post('/admin/lottery/reparse', [KeralaLotteryController::class, 'adminReparse'])->name('admin.lottery.reparse');
    Route::post('/admin/lottery/{result}/official-url', [KeralaLotteryController::class, 'adminUpdateOfficialUrl'])->name('admin.lottery.update-url');

    // Gold Rates Admin
    Route::get('/admin/gold-rates', [AdminController::class, 'goldRatesIndex'])->name('admin.gold-rates.index');
    Route::post('/admin/gold-rates/sync', [AdminController::class, 'goldRatesSync'])->name('admin.gold-rates.sync');
    Route::post('/admin/gold-rates/approve/{id}', [AdminController::class, 'goldRatesApprove'])->name('admin.gold-rates.approve');
    Route::post('/admin/gold-rates/reject/{id}', [AdminController::class, 'goldRatesReject'])->name('admin.gold-rates.reject');
});
