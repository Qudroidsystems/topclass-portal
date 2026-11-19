<?php

use App\Http\Controllers\Website\SiteController;
use App\Http\Controllers\Website\WebsiteAdminController as WA;
use App\Http\Controllers\Website\YearbookController as YC;
use App\Http\Controllers\Website\YearbookAdminController as YA;
use App\Http\Controllers\Website\AlumniAdminController as AA;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| School website (public) and its admin (portal › Website)
|--------------------------------------------------------------------------
| Included from routes/web.php. The home page "/" is registered there.
*/

Route::name('site.')->group(function () {
    Route::get('/website-preview', [SiteController::class, 'home'])->name('preview');
    Route::get('/pages/{slug}', [SiteController::class, 'page'])->name('page');
    Route::get('/news', [SiteController::class, 'news'])->name('news');
    Route::get('/news/{slug}', [SiteController::class, 'post'])->name('post');
    Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery');
    Route::get('/gallery/{slug}', [SiteController::class, 'album'])->name('album');
    Route::get('/our-people', [SiteController::class, 'people'])->name('people');
    Route::get('/academics', [SiteController::class, 'academics'])->name('academics');
    Route::get('/admissions', [SiteController::class, 'admissions'])->name('admissions');
    Route::post('/admissions/apply', [SiteController::class, 'apply'])->middleware('throttle:6,10')->name('apply');
    Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
    Route::post('/contact', [SiteController::class, 'sendContact'])->middleware('throttle:6,10')->name('contact.send');
    Route::post('/newsletter', [SiteController::class, 'newsletter'])->middleware('throttle:6,10')->name('newsletter');
    Route::get('/school-calendar', [SiteController::class, 'calendar'])->name('calendar');
    Route::get('/app', [SiteController::class, 'app'])->name('app');
    Route::get('/app/download', [SiteController::class, 'apk'])->name('app.apk');
    Route::get('/media/{path}', [SiteController::class, 'media'])->where('path', 'website/.+')->name('media');

    // Yearbooks & alumni
    Route::get('/yearbooks', [YC::class, 'index'])->name('yearbooks');
    Route::get('/yearbooks/{slug}', [YC::class, 'show'])->name('yearbook');
    Route::get('/yearbooks/{slug}/print', [YC::class, 'print'])->name('yearbook.print');
    Route::get('/yearbooks/{slug}/{id}', [YC::class, 'entry'])->whereNumber('id')->name('yearbook.entry');
    Route::get('/yearbook/upload/{token}', [YC::class, 'uploadForm'])->where('token', '[A-Za-z0-9]{20,64}')->name('yearbook.upload');
    Route::post('/yearbook/upload/{token}', [YC::class, 'uploadSave'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:10,10')->name('yearbook.upload.save');
    Route::get('/alumni', [YC::class, 'alumni'])->name('alumni');
    Route::get('/alumni/join', [YC::class, 'join'])->name('alumni.join');
    Route::post('/alumni/join', [YC::class, 'joinSave'])->middleware('throttle:5,10')->name('alumni.join.save');
    Route::get('/alumni/update/{token}', [YC::class, 'edit'])->where('token', '[A-Za-z0-9]{20,64}')->name('alumni.edit');
    Route::post('/alumni/update/{token}', [YC::class, 'editSave'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:10,10')->name('alumni.edit.save');
    Route::get('/alumni/{id}', [YC::class, 'alumnus'])->whereNumber('id')->name('alumnus');
});

Route::middleware('auth')->prefix('admin/website')->name('website.admin.')->group(function () {
    Route::get('/', [WA::class, 'index'])->name('index');
    Route::get('/settings', [WA::class, 'settings'])->name('settings');
    Route::post('/settings', [WA::class, 'saveSettings'])->name('settings.save');

    Route::get('/gallery', [WA::class, 'albums'])->name('albums');
    Route::post('/gallery', [WA::class, 'saveAlbum'])->name('albums.store');
    Route::get('/gallery/{id}', [WA::class, 'album'])->whereNumber('id')->name('album');
    Route::post('/gallery/{id}', [WA::class, 'saveAlbum'])->whereNumber('id')->name('album.save');
    Route::post('/gallery/{id}/photos', [WA::class, 'uploadPhotos'])->whereNumber('id')->name('album.upload');
    Route::post('/gallery/{id}/photos/update', [WA::class, 'updatePhotos'])->whereNumber('id')->name('album.photos');
    Route::delete('/gallery/{id}', [WA::class, 'deleteAlbum'])->whereNumber('id')->name('album.delete');

    Route::get('/messages', [WA::class, 'messages'])->name('messages');
    Route::get('/messages/export', [WA::class, 'exportMessages'])->name('messages.export');
    Route::post('/messages/{id}/read', [WA::class, 'readMessage'])->whereNumber('id')->name('messages.read');
    Route::delete('/messages/{id}', [WA::class, 'deleteMessage'])->whereNumber('id')->name('messages.delete');

    Route::get('/app', [WA::class, 'app'])->name('app');
    Route::post('/app', [WA::class, 'saveApp'])->name('app.save');

    Route::get('/people/staff-options', [WA::class, 'staffOptions'])->name('people.staff');
    Route::post('/people/import', [WA::class, 'importStaff'])->name('people.import');

    Route::get('/{type}', [WA::class, 'list'])->where('type', '[a-z]+')->name('list');
    Route::get('/{type}/new', [WA::class, 'create'])->where('type', '[a-z]+')->name('create');
    Route::post('/{type}', [WA::class, 'store'])->where('type', '[a-z]+')->name('store');
    Route::get('/{type}/{id}', [WA::class, 'edit'])->where('type', '[a-z]+')->whereNumber('id')->name('edit');
    Route::post('/{type}/{id}', [WA::class, 'update'])->where('type', '[a-z]+')->whereNumber('id')->name('update');
    Route::delete('/{type}/{id}', [WA::class, 'destroy'])->where('type', '[a-z]+')->whereNumber('id')->name('destroy');
});

/*
| Portal › Yearbook & Alumni (permission: Manage yearbook)
*/
Route::middleware('auth')->prefix('admin/yearbooks')->name('yearbook.admin.')->group(function () {
    Route::get('/', [YA::class, 'index'])->name('index');
    Route::post('/', [YA::class, 'store'])->name('store');
    Route::get('/{id}', [YA::class, 'show'])->whereNumber('id')->name('show');
    Route::get('/{id}/settings', [YA::class, 'settings'])->whereNumber('id')->name('settings');
    Route::post('/{id}/settings', [YA::class, 'update'])->whereNumber('id')->name('update');
    Route::delete('/{id}', [YA::class, 'destroy'])->whereNumber('id')->name('destroy');
    Route::get('/{id}/candidates', [YA::class, 'candidates'])->whereNumber('id')->name('candidates');
    Route::post('/{id}/import', [YA::class, 'import'])->whereNumber('id')->name('import');
    Route::post('/{id}/bulk', [YA::class, 'bulk'])->whereNumber('id')->name('bulk');
    Route::post('/{id}/photos', [YA::class, 'bulkPhotos'])->whereNumber('id')->name('photos');
    Route::get('/{id}/links.csv', [YA::class, 'exportLinks'])->whereNumber('id')->name('links');
    Route::get('/{id}/students/new', [YA::class, 'createEntry'])->whereNumber('id')->name('entry.create');
    Route::post('/{id}/students', [YA::class, 'saveEntry'])->whereNumber('id')->name('entry.store');
    Route::get('/{id}/students/{entry}', [YA::class, 'editEntry'])->whereNumber(['id', 'entry'])->name('entry');
    Route::post('/{id}/students/{entry}', [YA::class, 'saveEntry'])->whereNumber(['id', 'entry'])->name('entry.save');
    Route::delete('/{id}/students/{entry}', [YA::class, 'deleteEntry'])->whereNumber(['id', 'entry'])->name('entry.delete');
    Route::post('/{id}/students/{entry}/approve', [YA::class, 'approve'])->whereNumber(['id', 'entry'])->name('entry.approve');
    Route::post('/{id}/students/{entry}/reject', [YA::class, 'reject'])->whereNumber(['id', 'entry'])->name('entry.reject');
    Route::post('/{id}/students/{entry}/new-link', [YA::class, 'newLink'])->whereNumber(['id', 'entry'])->name('entry.link');
});

Route::middleware('auth')->prefix('admin/alumni')->name('alumni.admin.')->group(function () {
    Route::get('/', [AA::class, 'index'])->name('index');
    Route::get('/export.csv', [AA::class, 'export'])->name('export');
    Route::get('/new', [AA::class, 'create'])->name('create');
    Route::post('/', [AA::class, 'save'])->name('store');
    Route::post('/bulk', [AA::class, 'bulk'])->name('bulk');
    Route::get('/{id}', [AA::class, 'edit'])->whereNumber('id')->name('edit');
    Route::post('/{id}', [AA::class, 'save'])->whereNumber('id')->name('save');
    Route::delete('/{id}', [AA::class, 'destroy'])->whereNumber('id')->name('destroy');
    Route::post('/{id}/status', [AA::class, 'status'])->whereNumber('id')->name('status');
    Route::post('/{id}/changes', [AA::class, 'changes'])->whereNumber('id')->name('changes');
    Route::get('/{id}/link', [AA::class, 'link'])->whereNumber('id')->name('link');
});
