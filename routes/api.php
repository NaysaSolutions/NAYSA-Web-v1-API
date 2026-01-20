<?php

use App\Http\Controllers\HSDocController;
use App\Http\Controllers\HSDropdownController;
use App\Http\Controllers\HSOptionController;
use App\Http\Controllers\HSColConfigController;
use App\Http\Controllers\HSRptController;
use App\Http\Controllers\HSToolsController;
use App\Http\Controllers\PrintingController;
use App\Http\Controllers\FileAttachmentController;

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BankTypeController;
use App\Http\Controllers\VATController;
use App\Http\Controllers\ATCController;
use App\Http\Controllers\CurrController;
use App\Http\Controllers\CutoffController;
use App\Http\Controllers\RCTypeController;
use App\Http\Controllers\RCMastController;
use App\Http\Controllers\DForexController;
use App\Http\Controllers\BankMasterController;
use App\Http\Controllers\COAMasterController;
use App\Http\Controllers\COAClassController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BillCodeController;
use App\Http\Controllers\PayTermController;
use App\Http\Controllers\BillTermController;
use App\Http\Controllers\VendMasterController;
use App\Http\Controllers\CustMasterController;
use App\Http\Controllers\SLMasterController;

use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\APVoucherController;
use App\Http\Controllers\APDMController;
use App\Http\Controllers\APCMController;
use App\Http\Controllers\CVController;
use App\Http\Controllers\PCVController;
use App\Http\Controllers\SVIController;
use App\Http\Controllers\SOAController;
use App\Http\Controllers\ARCMController;
use App\Http\Controllers\ARDMController;
use App\Http\Controllers\CRController;

use App\Http\Controllers\ARController;
use App\Http\Controllers\ARBalanceController;
use App\Http\Controllers\GLBalanceController;
use App\Http\Controllers\APBalanceController;
use App\Http\Controllers\AllBIRController;

use App\Http\Controllers\MenuController;
use App\Http\Controllers\AccessRightsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MailController;

use App\Http\Controllers\POController;
use App\Http\Controllers\PRController;
use App\Http\Controllers\RRController;
use App\Http\Controllers\JOController;
use App\Http\Controllers\MSMastController;
use App\Http\Controllers\WarehouseMastController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MSISController;
use App\Http\Controllers\MSSTController;
use App\Http\Controllers\MSAJController;
use App\Http\Controllers\MSRRController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/companies', [AuthController::class, 'companies']);
Route::post('/send-mail', [MailController::class, 'send']);

Route::middleware('tenant')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);

    /** ✅ ADDED (from attached api.php) — you had these commented out */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/heartbeat', [AuthController::class, 'heartbeat']);

    Route::post('/upsertCompany', [CompanyController::class, 'upsert']);
    Route::get('/getCompany', [CompanyController::class, 'get']);

    Route::get('/getUser', [UserController::class, 'get']);
    Route::get('/load', [UserController::class, 'load']);
    Route::post('/users/upsert', [UserController::class, 'upsert']);
    Route::post('/users/approve', [UserController::class, 'approveAccount']);
    Route::post('/users/delete', [UserController::class, 'delete']);
    Route::post('/users/request-password-reset', [UserController::class, 'requestPasswordReset']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    // Heart Strong
    Route::get('/getHSDoc', [HSDocController::class, 'get']);
    Route::post('/getHSDropdown', [HSDropdownController::class, 'get']);
    Route::get('/getHSOption', [HSOptionController::class, 'get']);
    Route::get('/getHSColConfig', [HSColConfigController::class, 'get']);

    Route::get('/menu-items', [MenuController::class, 'items']);
    Route::get('/menu-routes', [MenuController::class, 'routes']);

    Route::get('/role', [AccessRightsController::class, 'loadRole']);
    Route::get('/getRole', [AccessRightsController::class, 'getRole']);
    Route::post('/deleteRole', [AccessRightsController::class, 'DeleteRole']);
    Route::get('/loadRole', [AccessRightsController::class, 'getUsers']);
    Route::get('/getRoleMenu', [AccessRightsController::class, 'getRoleMenu']);
    Route::get('/getUserRoles', [AccessRightsController::class, 'getUserRoles']);
    Route::post('/upsertRole', [AccessRightsController::class, 'UpsertRole']);
    Route::post('/upsertRoleMenu', [AccessRightsController::class, 'upsertRoleMenu']);
    Route::post('/UpsertUserRole', [AccessRightsController::class, 'UpsertUserRole']);
    Route::post('/upsert', [AccessRightsController::class, 'upsert']);
    Route::post('/deleteUserRole', [AccessRightsController::class, 'deleteUserRole']);
    Route::get('/getUserRoles', [AccessRightsController::class, 'getUserRoles']);

    //Printing
    Route::prefix('printing')->group(function () {
        Route::post('/handle', [PrintingController::class, 'handlePrinting']);
        Route::get('/pending', [PrintingController::class, 'loadPendingDocuments']);
        Route::post('/update-generated', [PrintingController::class, 'updateGeneratedDocument']);
        Route::get('/generate-id', [PrintingController::class, 'generateId']);
    });

    Route::get('/hsrpt', [HSRptController::class, 'index']);
    Route::get('/getHsrpt', [HSRptController::class, 'get']);
    Route::get('/getHSTblColLen', [HSToolsController::class, 'getTblGetFieldLenght']);

    Route::post('/printForm', [PrintingController::class, 'printForm']);
    Route::post('/printARReport', [PrintingController::class, 'printARReport']);
    Route::post('/printAPReport', [PrintingController::class, 'printAPReport']);
    Route::post('/printGLReport', [PrintingController::class, 'printGLReport']);
    Route::post('/exportHistoryReport', [PrintingController::class, 'exportHistoryReport']);
    Route::post('/upsertDocSign', [PrintingController::class, 'upsertDocSign']);
    Route::get('/getDocSign', [PrintingController::class, 'getDocSign']);

    Route::post('/attachFile', [FileAttachmentController::class, 'attachFile']);
    Route::delete('/deleteFile/{id}', [FileAttachmentController::class, 'deleteFile']);
    Route::get('/downloadAll/{documentID}', [FileAttachmentController::class, 'downloadAll']);
    Route::get('/downloadFile/{id}', [FileAttachmentController::class, 'downloadFile']);
    Route::get('/getAttachFile', [FileAttachmentController::class, 'get']);

    Route::get('/getOpenAPBalance', [APBalanceController::class, 'getOpenAPBalance']);
    Route::get('/getSelectedAPBalance', [APBalanceController::class, 'getSelectedAPBalance']);
    Route::get('/getAPInquiry', [APBalanceController::class, 'getAPInquiry']);
    Route::get('/getAPAging', [APBalanceController::class, 'getAPAging']);
    Route::get('/getAPAdvances', [APBalanceController::class, 'getAPAdvances']);
    Route::get('/getAPCheckRelasing', [APBalanceController::class, 'getAPCheckRelasing']);
    Route::post('/getAPCheckRelasing', [APBalanceController::class, 'getAPCheckRelasing']);
    Route::post('/updateAPCKRL', [APBalanceController::class, 'updateAPCKRL']);

    Route::get('/getOpenARBalance', [ARBalanceController::class, 'getOpenARBalance']);
    Route::get('/getSelectedARBalance', [ARBalanceController::class, 'getSelectedARBalance']);
    Route::get('/getARInquiry', [ARBalanceController::class, 'getARInquiry']);
    Route::get('/getARAging', [ARBalanceController::class, 'getARAging']);
    Route::get('/getARAdvances', [ARBalanceController::class, 'getARAdvances']);
    Route::get('/getARCWLCLInquiry', [ARBalanceController::class, 'getARCWLCLInquiry']);

    Route::get('/getGLInquiry', [GLBalanceController::class, 'getGLInquiry']);
    Route::get('/getSLInquiry', [GLBalanceController::class, 'getSLInquiry']);
    Route::get('/getTBSummary', [GLBalanceController::class, 'getTBSummary']);

    Route::get('/getEWTInquiry', [AllBIRController::class, 'getEWTInquiry']);
    Route::get('/getCWTInquiry', [AllBIRController::class, 'getCWTInquiry']);
    Route::get('/getINTAXInquiry', [AllBIRController::class, 'getINTAXInquiry']);
    Route::get('/getOUTAXInquiry', [AllBIRController::class, 'getOUTAXInquiry']);

    Route::get('/bankType', [BankTypeController::class, 'index']);
    Route::post('/upsertBankType', [BankTypeController::class, 'upsert']);
    Route::get('/lookupBankType', [BankTypeController::class, 'lookup']);
    Route::post('/deleteBankType', [BankTypeController::class, 'delete']);

    Route::get('/curr', [CurrController::class, 'index']);
    Route::post('/upsertCurr', [CurrController::class, 'upsert']);
    Route::get('/lookupCurr', [CurrController::class, 'lookup']);
    Route::get('/getCurr', [CurrController::class, 'get']);
    Route::post('/deleteCurr', [CurrController::class, 'delete']);

    Route::get('/vat', [VATController::class, 'index']);
    Route::post('/upsertVat', [VATController::class, 'upsert']);
    Route::get('/lookupVat', [VATController::class, 'lookup']);
    Route::get('/getVat', [VATController::class, 'get']);
    Route::post('/deleteVat', [VATController::class, 'delete']);

    Route::get('/atc', [ATCController::class, 'index']);
    Route::post('/upsertATC', [ATCController::class, 'upsert']);
    Route::post('/lookupATC', [ATCController::class, 'lookup']);
    Route::get('/getATC', [ATCController::class, 'get']);

    Route::get('/cutOff', [CutoffController::class, 'index']);
    Route::post('/upsertCutOff', [CutoffController::class, 'upsert']);
    Route::get('/lookupCutOff', [CutoffController::class, 'lookup']);
    Route::get('/getCutOff', [CutoffController::class, 'get']);
    Route::post('/deleteCutOff', [CutoffController::class, 'delete']);

    Route::get('/rCType', [RCTypeController::class, 'index']);
    Route::post('/upsertRCType', [RCTypeController::class, 'upsert']);

    Route::get('/rCMast', [RCMastController::class, 'index']);
    Route::post('/upsertRCMast', [RCMastController::class, 'upsert']);
    Route::get('/lookupRCMast', [RCMastController::class, 'lookup']);
    Route::get('/getRCMast', [RCMastController::class, 'get']);

    Route::get('/dForex', [DForexController::class, 'index']);
    Route::post('/upsertDForex', [DForexController::class, 'upsert']);
    Route::post('/getDForex', [DForexController::class, 'get']);

    Route::get('/bank', [BankMasterController::class, 'index']);
    Route::post('/upsertBank', [BankMasterController::class, 'upsert']);
    Route::get('/lookupBank', [BankMasterController::class, 'lookup']);
    Route::get('/getBank', [BankMasterController::class, 'get']);
    Route::get('/getDuplicate', [BankMasterController::class, 'getDuplicateCheck']);

    Route::get('/cOA', [COAMasterController::class, 'index']);
    Route::post('/upsertCOA', [COAMasterController::class, 'upsert']);
    Route::post('/lookupCOA', [COAMasterController::class, 'lookup']);
    Route::get('/getCOA', [COAMasterController::class, 'get']);
    Route::post('/lookupGL', [COAMasterController::class, 'lookupGL']);
    Route::post('/editEntries', [COAMasterController::class, 'editEntries']);

    Route::get('/cOAClass', [COAClassController::class, 'index']);
    Route::post('/upsertCOAClass', [COAClassController::class, 'upsert']);

    Route::get('/branch', [BranchController::class, 'index']);
    Route::post('/upsertBranch', [BranchController::class, 'upsert']);
    Route::get('/lookupBranch', [BranchController::class, 'lookup']);
    Route::get('/getBranch', [BranchController::class, 'get']);
    Route::post('/deleteBranch', [BranchController::class, 'delete']);

    Route::get('/billcode', [BillCodeController::class, 'index']);
    Route::post('/upsertBillcode', [BillCodeController::class, 'upsert']);
    Route::get('/lookupBillcode', [BillCodeController::class, 'lookup']);
    Route::get('/getBillcode', [BillCodeController::class, 'get']);

    Route::get('/payterm', [PayTermController::class, 'index']);
    Route::post('/upsertPayterm', [PayTermController::class, 'upsert']);
    Route::get('/lookupPayterm', [PayTermController::class, 'lookup']);
    Route::get('/getPayterm', [PayTermController::class, 'get']);

    Route::get('/billterm', [BillTermController::class, 'index']);
    Route::post('/upsertBillterm', [BillTermController::class, 'upsert']);
    Route::get('/lookupBillterm', [BillTermController::class, 'lookup']);
    Route::get('/getBillterm', [BillTermController::class, 'get']);

    Route::get('/vendMast', [VendMasterController::class, 'index']);
    Route::post('/upsertVendMast', [VendMasterController::class, 'upsert']);
    Route::get('/lookupVendMast', [VendMasterController::class, 'lookup']);
    Route::post('/getVendMast', [VendMasterController::class, 'get']);

    Route::get('/payee', [VendMasterController::class, 'index']);
    Route::post('/upsertPayee', [VendMasterController::class, 'upsert']);
    Route::get('/lookupPayee', [VendMasterController::class, 'lookup']);
    Route::post('/getPayee', [VendMasterController::class, 'get']);
    Route::post('/addPayeeDetail', [VendMasterController::class, 'addDetail']);

    Route::get('/customer', [CustMasterController::class, 'index']);
    Route::post('/upsertCustomer', [CustMasterController::class, 'upsert']);
    Route::get('/lookupCustomer', [CustMasterController::class, 'lookup']);
    Route::post('/getCustomer', [CustMasterController::class, 'get']);
    Route::post('/addCustomerDetail', [CustMasterController::class, 'addDetail']);

    Route::get('/sl', [SLMasterController::class, 'index']);
    Route::post('/upsertSL', [SLMasterController::class, 'upsert']);
    Route::get('/lookupSL', [SLMasterController::class, 'lookup']);
    Route::get('/getSL', [SLMasterController::class, 'get']);

    Route::get('/MSMast', [MSMastController::class, 'index']);
    Route::post('/upsertMSMast', [MSMastController::class, 'upsert']);
    Route::get('/lookupMSMast', [MSMastController::class, 'lookup']);
    Route::get('/getMSMast', [MSMastController::class, 'get']);

    // Transactions
    Route::get('/jV', [JournalVoucherController::class, 'index']);
    Route::post('/upsertJV', [JournalVoucherController::class, 'upsert']);
    Route::post('/generateGLJV', [JournalVoucherController::class, 'generateGL']);
    Route::get('/getJV', [JournalVoucherController::class, 'get']);
    Route::get('/postingJV', [JournalVoucherController::class, 'posting']);
    Route::get('/reversalJV', [JournalVoucherController::class, 'reversal']);
    Route::post('/getJVHistory', [JournalVoucherController::class, 'history']);

    Route::get('/APV', [APVoucherController::class, 'index']);
    Route::post('/upsertAPV', [APVoucherController::class, 'upsert']);
    Route::post('/getAPV', [APVoucherController::class, 'get']);
    Route::post('/generateGLAPV', [APVoucherController::class, 'generateGL']);
    Route::post('/load-history', [APVoucherController::class, 'load']);
    Route::post('/PostAPVTransaction', [APVoucherController::class, 'PostTransaction']);
    Route::post('/getAPVHistory', [APVoucherController::class, 'history']);

    Route::get('/PO', [POController::class, 'index']);
    Route::post('/upsertPO', [POController::class, 'upsert']);
    Route::post('/getPO', [POController::class, 'get']);
    Route::post('/getPOOpen', [POController::class, 'getPOOpen']);

    Route::get('/JO', [JOController::class, 'index']);
    Route::post('/upsertJO', [JOController::class, 'upsert']);
    Route::post('/getJO', [JOController::class, 'get']);

    Route::get('/PR', [PRController::class, 'index']);
    Route::post('/upsertPR', [PRController::class, 'upsert']);
    Route::get('/getPR', [PRController::class, 'get']);
    Route::post('/getPROpen', [PRController::class, 'getPROpen']);
    Route::post('/po/update', [POController::class, 'updatePrFromPO']);

    Route::get('/MSRR', [MSRRController::class, 'index']);
    Route::post('/upsertMSRR', [MSRRController::class, 'upsert']);
    Route::post('/generateGLMSRR', [MSRRController::class, 'generateGL']);
    Route::get('/getMSRR', [MSRRController::class, 'get']);
    Route::get('/postingMSRR', [MSRRController::class, 'posting']);
    Route::get('/findMSRR', [MSRRController::class, 'find']);
    

    Route::get('/MSIS', [MSISController::class, 'index']);
    Route::post('/upsertMSIS', [MSISController::class, 'upsert']);
    Route::post('/generateGLMSIS', [MSISController::class, 'generateGL']);
    Route::get('/getMSIS', [MSISController::class, 'get']);
    Route::get('/postingMSIS', [MSISController::class, 'posting']);

    Route::get('/MSST', [MSSTController::class, 'index']);
    Route::post('/upsertMSST', [MSSTController::class, 'upsert']);
    Route::post('/generateGLMSST', [MSSTController::class, 'generateGL']);
    Route::get('/getMSST', [MSSTController::class, 'get']);
    Route::get('/postingMSST', [MSSTController::class, 'posting']);


    Route::get('/MSAJ', [MSAJController::class, 'index']);
    Route::post('/upsertMSAJ', [MSAJController::class, 'upsert']);
    Route::post('/generateGLMSAJ', [MSAJController::class, 'generateGL']);
    Route::get('/getMSAJ', [MSAJController::class, 'get']);
    Route::get('/postingMSAJ', [MSAJController::class, 'posting']);



    Route::prefix('warehouse')->group(function () {
        Route::get('/warehouse',   [WarehouseMastController::class, 'load']);
        Route::get('/getWarehouse',    [WarehouseMastController::class, 'get']);      // ?whCode=WH001
        Route::get('/lookupWarehouse', [WarehouseMastController::class, 'lookup']);   // ?filter=ActiveAll
        Route::post('/upsertWarehouse', [WarehouseMastController::class, 'upsert']);
    });

    Route::prefix('location')->group(function () {
        Route::get('/location',   [LocationController::class, 'load']);
        Route::get('/getLocation',    [LocationController::class, 'get']);           // ?locCode=L001
        Route::get('/lookupLocation', [LocationController::class, 'lookup']);        // ?filter=ActiveAll
        Route::post('/upsertLocation', [LocationController::class, 'upsert']);
    });

    Route::post('/msLookup', [MSISController::class, 'msLookup']);

    Route::get('/aPCM', [APCMController::class, 'index']);
    Route::post('/upsertAPCM', [APCMController::class, 'upsert']);
    Route::post('/generateGLAPCM', [APCMController::class, 'generateGL']);
    Route::get('/getAPCM', [APCMController::class, 'get']);
    Route::get('/postingAPCM', [APCMController::class, 'posting']);
    Route::post('/getAPCMHistory', [APCMController::class, 'history']);

    Route::get('/aPDM', [APDMController::class, 'index']);
    Route::post('/upsertAPDM', [APDMController::class, 'upsert']);
    Route::post('/generateGLAPDM', [APDMController::class, 'generateGL']);
    Route::get('/getAPDM', [APDMController::class, 'get']);
    Route::get('/postingAPDM', [APDMController::class, 'posting']);
    Route::post('/getAPDMHistory', [APDMController::class, 'history']);

    Route::get('/cV', [CVController::class, 'index']);
    Route::post('/upsertCV', [CVController::class, 'upsert']);
    Route::post('/generateGLCV', [CVController::class, 'generateGL']);
    Route::post('/postCV', [CVController::class, 'post']);
    Route::get('/getCV', [CVController::class, 'get']);
    Route::get('/postingCV', [CVController::class, 'posting']);
    Route::post('/load-CVhistory', [CVController::class, 'load']);
    Route::post('/getCVHistory', [CVController::class, 'history']);

    Route::get('/pCV', [PCVController::class, 'index']);
    Route::post('/upsertPCV', [PCVController::class, 'upsert']);
    Route::post('/generateGLPCV', [PCVController::class, 'generateGL']);
    Route::get('/getPCV', [PCVController::class, 'get']);
    Route::get('/postingPCV', [PCVController::class, 'posting']);
    Route::post('/getPCVHistory', [PCVController::class, 'history']);

    Route::get('/sVI', [SVIController::class, 'index']);
    Route::post('/upsertSVI', [SVIController::class, 'upsert']);
    Route::post('/generateGLSVI', [SVIController::class, 'generateGL']);
    Route::get('/getSVI', [SVIController::class, 'get']);
    Route::get('/postingSVI', [SVIController::class, 'posting']);
    Route::post('/getSVIHistory', [SVIController::class, 'history']);
    Route::get('/findSVI', [SVIController::class, 'find']);

    Route::get('/sOA', [SOAController::class, 'index']);
    Route::post('/upsertSOA', [SOAController::class, 'upsert']);
    Route::post('/generateGLSOA', [SOAController::class, 'generateGL']);
    Route::get('/getSOA', [SOAController::class, 'get']);
    Route::get('/postingSOA', [SOAController::class, 'posting']);
    Route::post('/getSOAHistory', [SOAController::class, 'history']);

    Route::get('/aRCM', [ARCMController::class, 'index']);
    Route::post('/upsertARCM', [ARCMController::class, 'upsert']);
    Route::post('/generateGLARCM', [ARCMController::class, 'generateGL']);
    Route::get('/getARCM', [ARCMController::class, 'get']);
    Route::get('/postingARCM', [ARCMController::class, 'posting']);
    Route::post('/getARCMHistory', [ARCMController::class, 'history']);

    Route::get('/aRDM', [ARDMController::class, 'index']);
    Route::post('/upsertARDM', [ARDMController::class, 'upsert']);
    Route::post('/generateGLARDM', [ARDMController::class, 'generateGL']);
    Route::get('/getARDM', [ARDMController::class, 'get']);
    Route::get('/postingARDM', [ARDMController::class, 'posting']);
    Route::post('/getARDMHistory', [ARDMController::class, 'history']);

    Route::post('/cR', [CRController::class, 'index']);
    Route::post('/upsertCR', [CRController::class, 'upsert']);
    Route::post('/getCR', [CRController::class, 'get']);
    Route::post('/generateGLCR', [CRController::class, 'generateGL']);
    Route::get('/getCR', [CRController::class, 'get']);
    Route::get('/postingCR', [CRController::class, 'posting']);
    Route::post('/getCRHistory', [CRController::class, 'history']);

    Route::post('/aR', [ARController::class, 'index']);
    Route::post('/upsertAR', [ARController::class, 'upsert']);
    Route::post('/getAR', [ARController::class, 'get']);
    Route::post('/generateGLAR', [ARController::class, 'generateGL']);
    Route::get('/getAR', [ARController::class, 'get']);
    Route::get('/postingAR', [ARController::class, 'posting']);
    Route::post('/getARHistory', [ARController::class, 'history']);
});

Route::group(['middleware' => ['tenant', 'posting.credential']], function () {
    Route::post('/finalizeAR',   [ARController::class,   'finalize']);
    Route::post('/finalizeCR',   [CRController::class,   'finalize']);
    Route::post('/finalizeARDM', [ARDMController::class, 'finalize']);
    Route::post('/finalizeARCM', [ARCMController::class, 'finalize']);
    Route::post('/finalizeSOA',  [SOAController::class,  'finalize']);
    Route::post('/finalizeSVI',  [SVIController::class,  'finalize']);
    Route::post('/finalizeJV',   [JournalVoucherController::class,  'finalize']);
    Route::post('/finalizePCV',  [PCVController::class, 'finalize']);
    Route::post('/finalizeCV',   [CVController::class, 'finalize']);
    Route::post('/finalizeAPDM', [APDMController::class, 'finalize']);
    Route::post('/finalizeAPCM', [APCMController::class, 'finalize']);
    Route::post('/finalizeMSRR', [MSRRController::class, 'finalize']);

    Route::post('/cancelARDM', [ARDMController::class, 'cancel']);
    Route::post('/cancelSOA', [SOAController::class, 'cancel']);
    Route::post('/cancelSVI', [SVIController::class, 'cancel']);
    Route::post('/cancelPCV', [PCVController::class, 'cancel']);
    Route::post('/cancelCV',  [CVController::class, 'cancel']);
    Route::post('/cancelAPV', [APVoucherController::class, 'cancel']);
    Route::post('/cancelJV',  [JournalVoucherController::class, 'cancel']);
    Route::post('/cancelARCM', [ARCMController::class, 'cancel']);
    Route::post('/cancelCR',  [CRController::class, 'cancel']);
    Route::post('/cancelAR',  [ARController::class, 'cancel']);
    Route::post('/cancelAPDM', [APDMController::class, 'cancel']);
    Route::post('/cancelAPCM', [APCMController::class, 'cancel']);

    Route::post('/cancelPR',  [PRController::class, 'cancel']);

    Route::post('/generateJVARCWLCL', [ARBalanceController::class, 'generateJVARCWLCL']);
});
