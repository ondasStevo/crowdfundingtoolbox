<?php


namespace Modules\Payment\Services;

use Illuminate\Http\Response;

use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\PaymentMethod;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\UserManagement\Entities\UserPaymentOption;
use Modules\UserManagement\Services\PortalUserService;
use Modules\UserManagement\Services\UserPaymentOptionService;
use Carbon\Carbon;

class PaymentService
{
    protected $paymentRepository;
    protected $donationService;
    protected $portalUserService;
    protected $userPaymentOptionService;

    private $transactionDateIndex;
    private $amountIndex;
    private $ibanIndex;
    private $variableSymbolIndex;
    private $payerReferenceIndex;
    private $paymentNoteIndex;
    private $transactionIdIndex;

    public function __construct(PaymentRepository $paymentRepository, UserPaymentOptionService $userPaymentOptionService,
                                DonationService $donationService, PortalUserService $portalUserService)
    {
        $this->paymentRepository = $paymentRepository;
        $this->donationService = $donationService;
        $this->portalUserService = $portalUserService;
        $this->userPaymentOptionService = $userPaymentOptionService;
    }

    public function create($request)
    {
        $valid = validator($request->only(
            'transaction_id',
            'variable_symbol',
            'iban',
            'amount',
            'transfer_type',
            'transaction_date',
            'created_by'
        ), [
            'transaction_id' => 'required|string|max:255',
            'variable_symbol' => 'bigInteger|max:255',
            'iban' => 'string|max:255',
            'amount' => 'required|decimal|max:255',
            'transfer_type' => 'required|integer',
            'transaction_date' => 'required|timestamp',
            'created_by' => 'required|string'
        ]);

        if ($valid->fails()) {
            $jsonError = response()->json([
                'error' => $valid->errors()
            ], 400);
            return $jsonError;
        }

        try {
            $this->createPayment($request);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => 'Successfully created payment.'
        ], Response::HTTP_CRETED);

    }

    public function createPayment($request)
    {
        $payment = $this->paymentRepository->create($request);
        return $payment;
    }

    public function update($request)
    {

    }

    public function getUnpairedPayments()
    {
        return response()->json(
            $this->paymentRepository->getUnpairedPayments(),
            Response::HTTP_OK);
    }

    private function getPayment($id)
    {
        return Payment::where('id', $id)->first();
    }

    public function pairPaymentToUser($request)
    {
        $valid = validator($request->only(
            'user_id',
            'payment_id',
            'iban'
        ), [
            'user_id' => 'required|integer',
            'payment_id' => 'required|integer',
            'iban' => 'required|string'
        ]);

        if ($valid->fails()) {
            $jsonError = response()->json([
                'error' => $valid->errors()
            ], 400);
            return $jsonError;
        }

        try {
            $unpairedPayments = $this->paymentRepository->getUnpairedPayments();
            $donations = $this->donationService->getDonationsByUserId($request['user_id']);
            $portal_user_id = $this->portalUserService->getPortalUserIdByUserId($request['user_id']);
            foreach ($unpairedPayments as $unPay) {
                if ($unPay->iban === $request['iban']) {
                    if ($donations == null) {
                        $donationRequest = array(
                            'amount' => $unPay->amount,
                            'is_monthly_donation' => false,
                            'portal_user_id' => $portal_user_id,
                            'widget_id' => 1,
                            'payment_method' => $unPay->transfer_type,
                            'status' => 'processed',
                            'payment_id' => $unPay->id
                        );
                        $this->donationService->create($donationRequest);
                    } else {
                        $paired = false;
                        $isPaymentIdNull = false;
                        foreach ($donations as $donation) {
                            // find first donation with same amount
                            if ($donation->payment_id == null) {
                                $isPaymentIdNull = true;
                            }
                            if (($donation->amount == $unPay->amount) && $isPaymentIdNull) {
                                $paired = true;
                                $this->donationService->updatePaymentIdAndAmount(array(
                                    'payment_id' => $request['payment_id'],
                                    'amount' => $unPay->amount
                                ), $donation->id);
                            }
                        }
                        if (!$paired) {
                            // pair to last donation with correct amount
                            if ($isPaymentIdNull) {
                                foreach ($donations as $donation) {
                                    // find first donation with same amount
                                    if ($donation->payment_id == null) {
                                        $isPaymentIdNull = true;
                                    }
                                    if ($isPaymentIdNull) {
                                        $this->donationService->updatePaymentIdAndAmount(array(
                                            'payment_id' => $request['payment_id'],
                                            'amount' => $unPay->amount
                                        ), $donation->id);
                                    }
                                }
                            } else {
                                $donationRequest = array(
                                    'amount' => $unPay->amount,
                                    'is_monthly_donation' => false,
                                    'portal_user_id' => $portal_user_id,
                                    'widget_id' => 1,
                                    'payment_method' => $unPay->transfer_type,
                                    'status' => 'processed',
                                    'payment_id' => $unPay->id
                                );
                                $this->donationService->create($donationRequest);
                            }
                        }
                    }
                    // ADD NEW IBAN TO PORTAL USER
                    $req = array(
                        'bank_account_number' => 'testik',
                        'pairing_type' => 'iban'
                    );
                    $this->userPaymentOptionService->update($req, $portal_user_id);
                }
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => 'Successfully paired payment to user.'
        ], Response::HTTP_OK);
    }

    public function pairPaymentsViaIban($request)
    {
        $valid = validator($request->only(
            'payment_ids'
        ), [
            'payment_ids' => 'required|array'
        ]);

        if ($valid->fails()) {
            $jsonError = response()->json([
                'error' => $valid->errors()
            ], 400);
            return $jsonError;
        }

        $allIds = sizeof($request['payment_ids']);
        $successPaired = 0;
        $message = '';

        try {
            foreach ($request['payment_ids'] as $payment_id) {
                $paymentData = $this->paymentRepository->get($payment_id);
                $portalUsers = $this->portalUserService->getAll();
                foreach ($portalUsers as $user) {
                    if ($user->portalUser->userPaymentOptions !== null) {
                        if ($paymentData->iban === $user->portalUser->userPaymentOptions->bank_account_number) {
                            $donations = $this->donationService->getDonationsByUserId($user->id);
                            if ($donations == null) {
                                $donationRequest = array(
                                    'amount' => $paymentData->amount,
                                    'is_monthly_donation' => false,
                                    'portal_user_id' => $user->portalUser->id,
                                    'widget_id' => 1,
                                    'payment_method' => $paymentData->transfer_type,
                                    'status' => 'processed',
                                    'payment_id' => $paymentData->id
                                );
                                $this->donationService->create($donationRequest);
                                $successPaired++;
                            } else {
                                $paired = false;
                                $isPaymentIdNull = false;
                                foreach ($donations as $donation) {
                                    // find first donation with same amount
                                    if ($donation->payment_id == null) {
                                        $isPaymentIdNull = true;
                                    }
                                    if (($donation->amount == $paymentData->amount) && $isPaymentIdNull) {
                                        $paired = true;
                                        $this->donationService->updatePaymentIdAndAmount(array(
                                            'payment_id' => $request['payment_id'],
                                            'amount' => $paymentData->amount
                                        ), $donation->id);
                                        $successPaired++;
                                    }
                                }
                                if (!$paired) {
                                    // pair to last donation with correct amount
                                    if ($isPaymentIdNull) {
                                        foreach ($donations as $donation) {
                                            // find first donation with same amount
                                            if ($donation->payment_id == null) {
                                                $isPaymentIdNull = true;
                                            }
                                            if ($isPaymentIdNull) {
                                                $this->donationService->updatePaymentIdAndAmount(array(
                                                    'payment_id' => $request['payment_id'],
                                                    'amount' => $paymentData->amount
                                                ), $donation->id);
                                                $successPaired++;
                                            }
                                        }
                                    } else {
                                        $donationRequest = array(
                                            'amount' => $paymentData->amount,
                                            'is_monthly_donation' => false,
                                            'portal_user_id' => $user->portalUser->id,
                                            'widget_id' => 1,
                                            'payment_method' => $paymentData->transfer_type,
                                            'status' => 'processed',
                                            'payment_id' => $paymentData->id
                                        );
                                        $this->donationService->create($donationRequest);
                                        $successPaired++;
                                    }
                                }
                            }
                            // ADD NEW IBAN TO PORTAL USER
                            $request = array(
                                'bank_account_number' => $paymentData->iban,
                                'pairing_type' => 'iban'
                            );
                            $this->userPaymentOptionService->update($request, $user->portalUser->id);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($successPaired !== $allIds) {
            $message = 'Not every user has in his account included IBAN, which is used in these payments. <br /><b>PAIRING STATUS:</b> ';
            if ($successPaired === 1) {
                $successMessage = $successPaired . ' payment was ';
            } else {
                $successMessage = $successPaired . ' payments were ';
            }
            if ($allIds - $successPaired === 1) {
                $notSuccessMessage = ($allIds - $successPaired) . ' payment was ';
            } else {
                $notSuccessMessage = ($allIds - $successPaired) . ' payments were ';
            }
            $message .= $successMessage . 'successfully paired and ' . $notSuccessMessage . ' not paired.';
        } else {
            $message = '<b>PAIRING STATUS:</b> All payments were successfully paired!';
        }
        $status = 'success';
        if ($allIds - $successPaired !== 0) {
            $status = 'warning';
        }

        return response()->json([
            'message' => $message,
            'status' => $status
        ], Response::HTTP_CREATED);


    }

    public function getPayments($from, $to, $monthly)
    {
        return $this->paymentRepository->getPayments($from, $to, $monthly);
    }

    public function getPaymentTotalGroupMonthly($from, $to)
    {
        return $this->paymentRepository->getPaymentTotalGroupMonthly($from, $to);
    }

    public function checkUplodedFileType()
    {
        $allowed = array('csv');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tmpname = $_FILES['file']['tmp_name'];
        $filename = $_FILES['file']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array($ext, $allowed) || finfo_file($finfo, $tmpname) !== 'text/plain') {
            return response()->json([
                'error' => 'Type of choosed file is not allowed. Only .csv files are allowed.'
            ], Response::HTTP_BAD_REQUEST);
        }
        return response()->json([
            'message' => 'Correct file type.'
        ], Response::HTTP_OK);
    }

    public function importPayments()
    {
        try {
            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', 2000);
            $tmpName = $_FILES['file']['tmp_name'];
            $csvAsArray = array_map('str_getcsv', file($tmpName));

            // bank transfer, card pay, pay by square, google pay, apple pay
            $transferTypes = [1, 2, 3, 4, 5];
            $cardPayIdentificator = 'CP ';


            // STATICALY DEFINED SHAPE OF IMPORT PAYMENTS - uses index from zero. Default table (Slovak standard bank export) looks like this:
            // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            // | Processing date | Settlement date | Sum | Currency | Type (credit/debet) | Prefix | Account number | Bank code | IBAN | Variable symbol | Specific symbol | Constant symbol | Payer reference | Information | Description |
            // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            $this->transactionDateIndex = 1; // Settlement date
            $this->amountIndex = 2; // Sum
            $this->ibanIndex = 8; // Iban
            $this->variableSymbolIndex = 9; // Variable symbol
            $this->payerReferenceIndex = 12; // Payer reference
            $this->paymentNoteIndex = 13; // Information
            $this->transactionIdIndex = 14; // Description (for example card pay information)

            $createdBy = 'import';


            $counter = 0;
            $countOfSuccessfullyCreatedRecords = 0;
            foreach ($csvAsArray as $csv) {
                if ($counter > 0) { // skip first row (header)
                    for ($i = 0; $i < $this->countOfNewRecords($csv, $this->checkSameRecords($csv, $csvAsArray)); $i++) {
                        // create record in payments table
                        $csvRequest = array(
                            'transaction_id' => $csv[$this->transactionIdIndex],
                            'iban' => $csv[$this->ibanIndex],
                            'amount' => (float)number_format((float)$csv[$this->amountIndex], 2, '.', ''),
                            'created_by' => $createdBy,
                            'transfer_type' => (substr($csv[$this->transactionIdIndex], 0, 3) === $cardPayIdentificator) ?
                                $transferTypes[1] : ((strpos(strtolower($csv[$this->paymentNoteIndex]), 'pay-by-square') !== false) ?
                                    $transferTypes[2] : $transferTypes[0]), //pay-by-square identificatior is in payment description
                            'variable_symbol' => (int)$csv[$this->variableSymbolIndex],
                            'transaction_date' => date('Y-m-d H:i:s', strtotime($csv[$this->transactionDateIndex])),
                            'payment_notes' => $csv[$this->paymentNoteIndex],
                            'payer_reference' => $csv[$this->payerReferenceIndex]
                        );
                        $this->paymentRepository->create($csvRequest);
                        // TODO: pair payment with donation
                        $countOfSuccessfullyCreatedRecords++;
                    }
                }
                $counter++;
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => 'Successfully imported ' . $countOfSuccessfullyCreatedRecords . ' payments.'
        ], Response::HTTP_CREATED);
    }

    // function which check all records in csv and find matches with row (for example, if some donor made donation more time per one day)
    private function checkSameRecords($row, $csvAsArray)
    {
        $counter = 0;
        foreach ($csvAsArray as $csv) {
            if ($csv === $row) {
                $counter++;
            }
        }
        return $counter;
    }

    // function which check, if payments table consists of some rows from csv
    private function countOfNewRecords($row, $timesOccurrence)
    {
        // TODO payments filter via IBAN!!!
        $occurances = 0;
        $payments = $this->paymentRepository->all();
        foreach ($payments as $p) {
            if ($p->iban === $row[$this->ibanIndex]
                && $p->variable_symbol === $row[$this->variableSymbolIndex]
                && $p->payment_notes === $row[$this->paymentNoteIndex]
                && abs(Carbon::createFromFormat('Y-m-d H:i:s', $p->transaction_date)->diffInSeconds() -
                    Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($row[$this->transactionDateIndex])))->diffInSeconds()) < 86400) { // is is lower than one day
                $occurances++;
            }
        }
        $countOfNewRecords = $timesOccurrence - $occurances;

        return $countOfNewRecords;
    }

}