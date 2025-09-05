<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\RolePermissionController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\VendorRequestController as AdminVendorRequestController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\VendorRequestController as WebVendorRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Products routes (admin only)
Route::middleware(['auth', 'verified', 'role:admin|super_admin'])->group(function () {
    Route::get('/admin/products', [App\Http\Controllers\Web\Admin\ProductController::class, 'index'])->name('products.index');
});

// Orders routes
Route::middleware(['auth', 'verified', 'role:admin|super_admin'])->group(function () {
    Route::get('/admin/orders', [App\Http\Controllers\Web\Admin\OrderController::class, 'index'])->name('orders.index');
});

// Vendor request routes (for customers)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('vendor-request/create', [WebVendorRequestController::class, 'create'])->name('vendor-request.create');
    Route::post('vendor-request', [WebVendorRequestController::class, 'store'])->name('vendor-request.store');
});

// Admin routes
Route::middleware(['auth', 'verified', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // User management
    Route::resource('users', UserController::class);
    
    // Vendor requests management
    Route::resource('vendor-requests', AdminVendorRequestController::class)->only(['index', 'show', 'update']);
});

// Super admin routes
Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Roles and permissions management
    Route::get('roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
    Route::post('roles', [RolePermissionController::class, 'createRole'])->name('roles-permissions.create-role');
    Route::post('permissions', [RolePermissionController::class, 'createPermission'])->name('roles-permissions.create-permission');
    Route::post('assign-role', [RolePermissionController::class, 'assignRole'])->name('roles-permissions.assign-role');
    Route::post('remove-role', [RolePermissionController::class, 'removeRole'])->name('roles-permissions.remove-role');
    Route::post('assign-permission', [RolePermissionController::class, 'assignPermission'])->name('roles-permissions.assign-permission');
    Route::post('remove-permission', [RolePermissionController::class, 'removePermission'])->name('roles-permissions.remove-permission');
});

require __DIR__.'/auth.php';
