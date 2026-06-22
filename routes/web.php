<?php

use App\Http\Controllers\Admin\PrintMemberQrCodeController;
use App\Http\Controllers\PrintMemberLoanController;
use App\Http\Controllers\PrintPosSaleReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/members/{user}/print-qr', PrintMemberQrCodeController::class)
        ->name('members.print-qr');

    Route::get('/members/loans/{loan}/print', PrintMemberLoanController::class)
        ->name('members.loans.print');

    Route::get('/admin/members/{user}/print-qr', PrintMemberQrCodeController::class)
        ->name('admin.members.print-qr');

    Route::get('/pos/sales/{sale}/receipt', PrintPosSaleReceiptController::class)
        ->name('pos.sales.print-receipt');
});
