<?php

use App\Http\Controllers\Admin\PrintMemberQrCodeController;
use App\Http\Controllers\PrintCreditPaymentReceiptController;
use App\Http\Controllers\PrintMemberLedgerController;
use App\Http\Controllers\PrintMemberLoanController;
use App\Http\Controllers\PrintPosSaleReceiptController;
use App\Http\Controllers\VerifyLoanApplicationController;
use App\Http\Controllers\VerifyMemberAccountController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal/login');

Route::get('/loans/{loan}/verify-email', VerifyLoanApplicationController::class)
    ->middleware('signed')
    ->name('loans.verify-email');

Route::get('/members/{member}/verify-email', VerifyMemberAccountController::class)
    ->middleware('signed')
    ->name('members.verify-email');

Route::get('/portal/service-worker.js', function () {
    return response(
        file_get_contents(public_path('portal-sw.js')),
        200,
        ['Content-Type' => 'application/javascript; charset=UTF-8'],
    );
})->name('portal.service-worker');

Route::middleware(['auth:web,member'])->group(function (): void {
    Route::get('/members/{member}/print-qr', [PrintMemberQrCodeController::class, 'member'])
        ->name('members.print-qr');

    Route::get('/members/loans/{loan}/print', PrintMemberLoanController::class)
        ->name('members.loans.print');

    Route::get('/admin/members/{member}/print-qr', [PrintMemberQrCodeController::class, 'member'])
        ->name('admin.members.print-qr');

    Route::get('/pos/sales/{sale}/receipt', PrintPosSaleReceiptController::class)
        ->name('pos.sales.print-receipt');

    Route::get('/pos/member-ledger/print', PrintMemberLedgerController::class)
        ->name('pos.member-ledger.print');

    Route::get('/pos/credit-payments/{payment}/receipt', PrintCreditPaymentReceiptController::class)
        ->name('pos.credit-payments.print-receipt');
});
