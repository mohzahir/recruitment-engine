<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\LeadDashboard;
use App\Livewire\CampaignDashboard; // تأكد من استدعاء الكلاس هنا
use App\Livewire\CampaignManager;
use App\Livewire\InboxManager;
use App\Livewire\LeadDirectory;
use App\Livewire\ContactManager;

Route::get('/', function () {
    return view('welcome');
});

// المسار القديم الخاص بك
Route::get('/dashboard', LeadDashboard::class);

// المسار الجديد لغرفة عمليات الحملات
Route::get('/campaigns', CampaignDashboard::class);

Route::get('/campaign-manager', CampaignManager::class);

Route::get('/inbox', InboxManager::class);

Route::get('/directory', LeadDirectory::class);

Route::get('/contacts', ContactManager::class);