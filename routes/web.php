<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\PortalController;
use App\Http\Controllers\Member\MemberController;
use App\Http\Controllers\Member\MemberSubscriptionController;
use App\Http\Controllers\Member\AccessBadgeController;
use App\Http\Controllers\Member\PartnerGroupController;
use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ScheduleController;
use App\Http\Controllers\Activity\TimeSlotController;
use App\Http\Controllers\Activity\ReservationController;

use App\Http\Controllers\Finance\ActivityPlanPriceController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\PricingController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\SubscriptionController;
use App\Http\Controllers\Finance\PlanController;
use App\Http\Controllers\Finance\SalesDashboardController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\StaffPlanningController;
use App\Http\Controllers\Staff\CoachController;
use App\Http\Controllers\Staff\CoachPlanningController;
use App\Http\Controllers\Staff\CoachReportingController;
use App\Http\Controllers\Staff\CoachAttendanceController;
use App\Http\Controllers\Reception\ReceptionController;

Route::get('/', [PortalController::class, 'index'])->name('portal.index')->middleware('auth');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'pgaudit'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // License Management
    Route::middleware('role:admin')->prefix('license')->name('admin.license.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\LicenseController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\LicenseController::class, 'update'])->name('update');
        Route::post('/toggle', [App\Http\Controllers\Admin\LicenseController::class, 'toggleStatus'])->name('toggle');
    });

    // Members (CRM)
    Route::middleware(['permission:members.view', 'module:crm'])->group(function () {
        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::get('members/search', [MemberController::class, 'search'])->name('members.search');
        Route::get('members/{member}/info', [MemberController::class, 'info'])->name('members.info');
    });
    Route::get('members/create', [MemberController::class, 'create'])->name('members.create')->middleware('permission:members.create');
    Route::post('members', [MemberController::class, 'store'])->name('members.store')->middleware('permission:members.create');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit')->middleware('permission:members.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update')->middleware('permission:members.edit');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy')->middleware('permission:members.delete');

    // Reception Area (Moved to CRM Module)
    Route::middleware(['role:admin,receptionniste', 'module:crm'])->prefix('reception')->group(function () {
        Route::get('/', [ReceptionController::class, 'index'])->name('reception.index');
        Route::get('/scan', [ReceptionController::class, 'scan'])->name('reception.scan');
        Route::post('member/add', [ReceptionController::class, 'storeMember'])->name('reception.member.store');
        Route::post('badge/update/{member}', [ReceptionController::class, 'updateBadge'])->name('reception.badge.update');
        Route::post('checkin/{member}', [ReceptionController::class, 'checkIn'])->name('reception.checkin');
        Route::post('checkin-badge', [ReceptionController::class, 'checkInByBadge'])->name('reception.checkin.badge');
        Route::post('checkin-group', [ReceptionController::class, 'confirmPartnerCheckIn'])->name('reception.checkin.group');

        // AJAX
        Route::get('search', [ReceptionController::class, 'search'])->name('reception.search');
        Route::get('member/{member}/logs', [ReceptionController::class, 'memberLogs'])->name('reception.member.logs');
        Route::get('today-accesses', [ReceptionController::class, 'todayAccesses'])->name('reception.today.accesses');
        
        // Reservations (Protected by CRM License)
        Route::get('reservations/search-members', [ReservationController::class, 'searchMembers'])->name('reservations.searchMembers');
        Route::get('reservations/search-groups', [ReservationController::class, 'searchGroups'])->name('reservations.searchGroups');
        Route::get('reservations/search', [ReservationController::class, 'search'])->name('reservations.search');
        Route::resource('reservations', ReservationController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy')->middleware('role:admin');
    });

    // Subscriptions (Protected by CRM License)
    Route::middleware(['module:crm'])->group(function() {
        Route::get('subscriptions/members', [SubscriptionController::class, 'indexMembers'])->name('subscriptions.members')->middleware('permission:subscriptions.view');
        Route::get('subscriptions/groups', [SubscriptionController::class, 'indexGroups'])->name('subscriptions.groups')->middleware('permission:subscriptions.view');
        
    
    // Pricing API
    Route::get('/finance/calculate-price', [PricingController::class, 'calculate'])
        ->name('finance.calculate-price');
        
    // Subscriptions Members
    Route::get('subscriptions/members/create', [SubscriptionController::class, 'createMember'])->name('subscriptions.members.create')->middleware('permission:subscriptions.create');
        Route::post('subscriptions/members', [SubscriptionController::class, 'storeMember'])->name('subscriptions.members.store')->middleware('permission:subscriptions.create');
        Route::get('subscriptions/members/{subscription}/edit', [SubscriptionController::class, 'editMember'])->name('subscriptions.members.edit')->middleware('permission:subscriptions.edit');
        Route::put('subscriptions/members/{subscription}', [SubscriptionController::class, 'updateMember'])->name('subscriptions.members.update')->middleware('permission:subscriptions.edit');
        
        // Partner Group Invoice Management (Global)
    Route::get('/finance/partner-invoices', [\App\Http\Controllers\Finance\PartnerInvoiceController::class, 'index'])->name('admin.finance.partner_invoices.index');
    Route::get('/finance/partner-invoices/{invoice}/pdf', [\App\Http\Controllers\Finance\PartnerInvoiceController::class, 'downloadPdf'])->name('admin.finance.partner_invoices.pdf');
    
    // Partner Group Invoice Management (Per Group)
        Route::get('subscriptions/groups/create', [SubscriptionController::class, 'createGroup'])->name('subscriptions.groups.create')->middleware('permission:subscriptions.create');
        Route::post('subscriptions/groups', [SubscriptionController::class, 'storeGroup'])->name('subscriptions.groups.store')->middleware('permission:subscriptions.create');
        Route::get('subscriptions/groups/{subscription}/edit', [SubscriptionController::class, 'editGroup'])->name('subscriptions.groups.edit')->middleware('permission:subscriptions.edit');
        Route::put('subscriptions/groups/{subscription}', [SubscriptionController::class, 'updateGroup'])->name('subscriptions.groups.update')->middleware('permission:subscriptions.edit');

        Route::middleware('permission:subscriptions.view')->get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        // Keep generic resource for backward compatibility/utilities if needed, but exclude index/create/store/edit/update if fully replaced.
        // Actually, let's keep it but put specific routes ABOVE it so they take precedence.
        Route::resource('subscriptions', SubscriptionController::class);

        // Payment AJAX
        Route::post('subscriptions/{subscription}/payments/ajax', [PaymentController::class, 'storeAjax'])
            ->name('subscriptions.payments.ajax')->middleware('permission:payments.create');
    });

    // Badges
    Route::get('badges/search', [AccessBadgeController::class, 'search'])->name('badges.search');
    Route::resource('badges', AccessBadgeController::class)->except(['show', 'destroy']);
    Route::delete('badges/{badge}', [AccessBadgeController::class, 'destroy'])->name('badges.destroy')->middleware('role:admin');

    // Roles & Permissions (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::get('roles/{id}/permissions', [RoleController::class, 'managePermissions'])->name('roles.permissions');
        Route::post('roles/{id}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        Route::resource('permissions', PermissionController::class)->except(['show']);
    });

    // Backups (Admin Only + License)
    Route::middleware(['role:admin', 'module:backups'])->prefix('backups')->name('admin.backups.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
        Route::post('/store', [App\Http\Controllers\Admin\BackupController::class, 'store'])->name('store');
        Route::get('/download/{id}', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
        Route::delete('/{id}', [App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('destroy');
        
        // Settings
        Route::post('/settings', [App\Http\Controllers\Admin\BackupSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-connection', [App\Http\Controllers\Admin\BackupSettingController::class, 'testConnection'])->name('settings.test-connection');
    });

    // Staff (Core - Available without HR License)
    Route::middleware('permission:staff.view')->get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/{id}/profile', [StaffController::class, 'show'])->name('staff.show');
    Route::middleware('permission:staff.create')->group(function () {
        Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    });
    Route::middleware('permission:staff.edit')->group(function () {
        Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    });
    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy')->middleware('permission:staff.delete');

    // HR & Advanced Staff Features (Protected by License)
    Route::middleware(['module:hr'])->group(function() {
        
        // Human Resources / Attendance (Admin Staff Area)
        Route::middleware('permission:staff.view')->prefix('staff/hr')->name('staff.hr.')->group(function () {
            Route::get('pointage', [\App\Http\Controllers\Staff\AttendanceController::class, 'pointage'])->name('pointage');
            Route::post('pointage', [\App\Http\Controllers\Staff\AttendanceController::class, 'storePointage'])->name('pointage.store');
            Route::get('dashboard', [\App\Http\Controllers\Staff\AttendanceController::class, 'index'])->name('dashboard');
            
            // Reports
            Route::get('reports/pdf', [\App\Http\Controllers\Staff\AttendanceReportController::class, 'exportPdf'])->name('reports.pdf');
            Route::get('reports/excel', [\App\Http\Controllers\Staff\AttendanceReportController::class, 'exportExcel'])->name('reports.excel');

            // Settings
            Route::get('settings', [\App\Http\Controllers\Staff\AttendanceSettingsController::class, 'edit'])->name('settings.edit');
            Route::post('settings', [\App\Http\Controllers\Staff\AttendanceSettingsController::class, 'update'])->name('settings.update');

            // Validation Validation
            Route::get('validation', [\App\Http\Controllers\Staff\AttendanceValidationController::class, 'index'])->name('validation.index');
            Route::post('validation/{id}/validate', [\App\Http\Controllers\Staff\AttendanceValidationController::class, 'validateAttendance'])->name('validation.validate');
            Route::post('validation/{id}/reject', [\App\Http\Controllers\Staff\AttendanceValidationController::class, 'reject'])->name('validation.reject');
            Route::post('validation/{id}/correct', [\App\Http\Controllers\Staff\AttendanceValidationController::class, 'correct'])->name('validation.correct');

            // Security / Access Control
            Route::get('security/simulator', [\App\Http\Controllers\Staff\AccessControlController::class, 'simulator'])->name('security.simulator');
            Route::get('security/logs', [\App\Http\Controllers\Staff\AccessControlController::class, 'logs'])->name('security.logs');
            Route::post('security/scan', [\App\Http\Controllers\Staff\AccessControlController::class, 'scan'])->name('security.scan');
        });

        // Staff Planning & Leaves
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('planning', [StaffPlanningController::class, 'index'])->name('planning.index');
            Route::get('planning/events', [StaffPlanningController::class, 'events'])->name('planning.events');
            Route::post('planning/store', [StaffPlanningController::class, 'storeSchedule'])->name('planning.store');
            Route::put('planning/{id}', [StaffPlanningController::class, 'updateSchedule'])->name('planning.update');
            Route::delete('planning/{id}', [StaffPlanningController::class, 'destroySchedule'])->name('planning.destroy')->middleware('role:admin');

            Route::get('leaves', [StaffPlanningController::class, 'indexLeaves'])->name('leaves.index');
            Route::post('leaves', [StaffPlanningController::class, 'storeLeave'])->name('leaves.store');
            Route::post('leaves/{id}/status', [StaffPlanningController::class, 'updateLeaveStatus'])->name('leaves.updateStatus');
            Route::delete('leaves/{id}', [StaffPlanningController::class, 'destroyLeave'])->name('leaves.destroy')->middleware('role:admin');
        });

        // Coaches (Protected by HR License)
        Route::resource('coaches', CoachController::class);
        
        // Coach Planning
        Route::get('coaches-planning', [CoachPlanningController::class, 'index'])->name('coaches.planning');
        Route::post('coaches-planning/update', [CoachPlanningController::class, 'updateSlot'])->name('coaches.planning.update');

        // Coach Reporting
        Route::get('coaches-reports', [CoachReportingController::class, 'index'])->name('coaches.reports.index');
        Route::get('coaches-reports/preview', [CoachReportingController::class, 'preview'])->name('coaches.reports.preview');
        Route::get('coaches-reports/export', [CoachReportingController::class, 'exportPdf'])->name('coaches.reports.export');
        Route::get('coaches-reports/export-excel', [CoachReportingController::class, 'exportExcel'])->name('coaches.reports.excel');
        Route::get('coaches-reports/global-export', [CoachReportingController::class, 'exportGlobalPdf'])->name('coaches.reports.global');
        Route::get('coaches-reports/global-export-excel', [CoachReportingController::class, 'exportGlobalExcel'])->name('coaches.reports.global.excel');

        // Coach Attendance
        Route::get('coaches-attendance', [CoachAttendanceController::class, 'index'])->name('coaches.attendance.index');


    });

    // Activities
    Route::middleware('permission:activities.view')->get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create')->middleware('permission:activities.create');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store')->middleware('permission:activities.create');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit')->middleware('permission:activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update')->middleware('permission:activities.edit');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy')->middleware('permission:activities.delete');

    // Partner Groups
    Route::resource('partner-groups', PartnerGroupController::class)->except(['destroy']);
    Route::prefix('partner-groups')->name('partner-groups.')->group(function() {
        Route::post('/{partnerGroup}/badges', [PartnerGroupController::class, 'addBadge'])->name('badges.add');
        Route::post('/{partnerGroup}/badges/{badge}/toggle', [PartnerGroupController::class, 'toggleBadgeStatus'])->name('badges.toggle');
        Route::delete('/{partnerGroup}/badges/{badge}', [PartnerGroupController::class, 'removeBadge'])->name('badges.remove');
        
        Route::post('/{partnerGroup}/slots', [PartnerGroupController::class, 'addSlot'])->name('slots.add');
        Route::delete('/{partnerGroup}/slots/{slot}', [PartnerGroupController::class, 'removeSlot'])->name('slots.remove');

        Route::post('/{partnerGroup}/subscription', [PartnerGroupController::class, 'storeSubscription'])->name('subscription.store');
    });
        

    Route::delete('partner-groups/{partner_group}', [PartnerGroupController::class, 'destroy'])->name('partner-groups.destroy')->middleware('role:admin');



    // Attendance Dashboard & Reports (Admin Only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard'])->name('attendance.dashboard');
        Route::get('/attendance/stats', [AttendanceController::class, 'stats'])->name('attendance.stats');
        Route::get('/attendance/export-pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.export.pdf');
    });

    // TimeSlots (admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('timeslots', TimeSlotController::class)->except(['show']);
    });

    // Schedule (Calendar) - Admin: full access, Réceptionniste: read-only, Others: no access
    Route::middleware('role:admin,receptionniste')->group(function () {
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/schedule/events', [ScheduleController::class, 'events'])->name('schedule.events');
        Route::get('/schedule/details/{id}', [ScheduleController::class, 'details'])->name('schedule.details');
    });
    // Only admin can modify schedule
    Route::middleware('role:admin')->group(function () {
        Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::put('/schedule/update/{id}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    });

    // Audit logs
    Route::middleware('role:admin')->prefix('audit')->name('audit.')->group(function () {
        Route::get('/dashboard', [AuditLogController::class, 'index'])->name('dashboard');
        Route::get('/show/{id}', [AuditLogController::class, 'show'])->name('show');
    });



    // Shop (Products) - Inventory
    Route::middleware('module:shop')->group(function() {
        Route::resource('products', ProductController::class);
        Route::get('products/image/{image}/delete', [ProductController::class, 'deleteImage'])->name('products.deleteImage');
        Route::resource('categories', CategoryController::class);
    });

    // Shop (POS) - Admin & Receptionniste
    Route::middleware(['role:admin,receptionniste', 'module:shop'])->group(function () {
        Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
        Route::get('/shop/sale/{id}/receipt', [ShopController::class, 'downloadReceipt'])->name('shop.sale.receipt'); // Shared Receipt Logic
        Route::get('/shop/sale/{id}/ticket', [ShopController::class, 'downloadTicket'])->name('shop.sale.ticket'); // Thermal Ticket Logic
        Route::post('/shop/checkout', [ShopController::class, 'store'])->name('shop.store');
        Route::get('/shop/members/search', [ShopController::class, 'searchMembers'])->name('shop.members.search');
    });
    
    // Sales Dashboard - Admin Only
    Route::middleware(['role:admin'])->get('/sales/dashboard', [SalesDashboardController::class, 'index'])->name('sales.dashboard');
});
Route::middleware(['auth', 'pgaudit', 'module:finance'])->prefix('finance')->group(function () {
    
    Route::middleware('permission:finance.view_stats')->group(function () {
        Route::get('/', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
        Route::get('/stats', [FinanceController::class, 'stats'])->name('finance.stats');
    });

    // Plans
    Route::resource('plans', PlanController::class)->except(['show', 'destroy']);
    Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy')->middleware('role:admin');



    // Payments
    Route::middleware('permission:payments.view')->get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create')->middleware('permission:payments.create');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store')->middleware('permission:payments.create');
    Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit')->middleware('permission:payments.edit');
    Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update')->middleware('permission:payments.edit');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy')->middleware('permission:payments.delete');
    
    Route::get('payments/export/excel', [PaymentController::class, 'exportExcel'])->name('payments.export.excel')->middleware('permission:payments.view');
    Route::get('payments/export/pdf', [PaymentController::class, 'exportPdf'])->name('payments.export.pdf')->middleware('permission:payments.view');
    Route::get('payments/{id}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');

    // Activity-Plan Prices
    Route::resource('activity-plan-prices', ActivityPlanPriceController::class)->except(['destroy']);
    Route::delete('activity-plan-prices/{activity_plan_price}', [ActivityPlanPriceController::class, 'destroy'])->name('activity-plan-prices.destroy')->middleware('role:admin');

    Route::get('activity-plan-prices/get-by-activity/{activity_id}', 
        [ActivityPlanPriceController::class, 'getByActivity']
    )->name('activity-plan-prices.getByActivity');

    // Expenses
    Route::middleware('permission:expenses.view')->get('expenses/export/pdf', [ExpenseController::class, 'exportPdf'])->name('expenses.export.pdf');
    Route::middleware('permission:expenses.view')->get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('permission:expenses.create');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('permission:expenses.create');
    Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit')->middleware('permission:expenses.edit');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware('permission:expenses.edit');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('permission:expenses.delete');
});



/*
|--------------------------------------------------------------------------
| Pool Maintenance Module
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'pgaudit', 'module:operations'])->prefix('pool')->name('pool.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Pool\PoolDashboardController::class, 'index'])->name('dashboard');

    // Subscriptions


    // Facilities
    Route::resource('facilities', App\Http\Controllers\Pool\FacilityController::class);

    // Water Quality
    Route::get('water-tests/export-pdf', [App\Http\Controllers\Pool\WaterQualityController::class, 'exportPdf'])->name('water-tests.export-pdf');
    Route::resource('water-tests', App\Http\Controllers\Pool\WaterQualityController::class);
    Route::get('water-tests/history/{pool}', [App\Http\Controllers\Pool\WaterQualityController::class, 'history'])->name('water-tests.history');

    // Equipment
    Route::resource('equipment', App\Http\Controllers\Pool\EquipmentController::class);
    Route::post('equipment/{equipment}/status', [App\Http\Controllers\Pool\EquipmentController::class, 'updateStatus'])->name('equipment.update-status');

    // Maintenance
    Route::resource('maintenance', App\Http\Controllers\Pool\MaintenanceController::class);
    Route::post('maintenance/{maintenance}/status', [App\Http\Controllers\Pool\MaintenanceController::class, 'updateStatus'])->name('maintenance.update-status');

    // Chemicals
    Route::get('chemicals', [App\Http\Controllers\Pool\ChemicalController::class, 'index'])->name('chemicals.index');
    Route::post('chemicals/usage', [App\Http\Controllers\Pool\ChemicalController::class, 'storeUsage'])->name('chemicals.store-usage');
    Route::post('chemicals/{chemical}/stock', [App\Http\Controllers\Pool\ChemicalController::class, 'updateStock'])->name('chemicals.update-stock');

    // Incidents
    Route::resource('incidents', App\Http\Controllers\Pool\IncidentController::class);

    // Daily/Weekly Tasks
    Route::get('tasks', [App\Http\Controllers\Pool\TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks/daily', [App\Http\Controllers\Pool\TaskController::class, 'storeDaily'])->name('tasks.store-daily');
    Route::post('tasks/weekly', [App\Http\Controllers\Pool\TaskController::class, 'storeWeekly'])->name('tasks.store-weekly');
    Route::post('tasks/monthly', [App\Http\Controllers\Pool\TaskController::class, 'storeMonthly'])->name('tasks.store-monthly');
    
    // Task Templates
    Route::resource('tasks/templates', App\Http\Controllers\Pool\TaskTemplateController::class)->names('tasks.templates');
});



