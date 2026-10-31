<?php

namespace App\Contracts\Services;

use App\Models\Complaint;
use App\Models\ComplaintResponse;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface ComplaintServiceInterface
{
    /**
     * Buyer submits an official complaint with mandatory evidence (UC-16).
     */
    public function fileComplaint(
        Transaction $transaction,
        User $buyer,
        string $reason,
        UploadedFile $evidenceFile
    ): Complaint;

    /**
     * Seller submits a response/clarification with optional evidence within 24 hours (UC-17).
     */
    public function respondToComplaint(
        Complaint $complaint,
        User $seller,
        string $responseText,
        ?UploadedFile $evidenceFile = null
    ): ComplaintResponse;

    /**
     * Admin requests additional evidence and extends deadline (UC-18).
     */
    public function requestAdditionalEvidence(
        Complaint $complaint,
        User $admin,
        string $reason,
        int $extensionHours = 24
    ): Complaint;

    /**
     * Admin makes final dispute resolution decision (PAYOUT or REFUND) (UC-18).
     */
    public function resolveComplaint(
        Complaint $complaint,
        User $admin,
        string $decision,
        string $decisionReason
    ): Complaint;
}

