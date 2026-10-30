<?php

namespace App\Services;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\ConversationServiceInterface;
use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\NotificationEventType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class AgreementService implements AgreementServiceInterface
{
    public function __construct(
        protected FeeCalculationServiceInterface $feeCalculationService,
        protected NotificationServiceInterface $notificationService,
        protected ConversationServiceInterface $conversationService,
    ) {}

    /**
     * Create an agreement from a marketplace listing.
     * Aturan bisnis marketplace: harga listing tetap, fee wajib ditambahkan ke Buyer (EXCLUDE),
     * Seller menerima harga listing penuh.
     *
     * @throws InvalidArgumentException
     */
    public function createFromListing(Listing $listing, User $buyer, User $seller): Agreement
    {
        $basePrice = (float) $listing->price;

        if ($basePrice < 5000) {
            throw new InvalidArgumentException(sprintf(
                'Harga dasar transaksi (Rp%s) di bawah batas minimum yang diizinkan (Rp5.000).',
                number_format($basePrice, 0, ',', '.')
            ));
        }

        $feeCalculation = $this->feeCalculationService->calculateForMarketplace($basePrice);

        return Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $listing->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => $basePrice,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => $feeCalculation->feeAmount,
            'buyer_total' => $feeCalculation->buyerTotal,
            'seller_net' => $feeCalculation->sellerNet,
            'delivery_method' => $listing->delivery_method,
            'delivery_within_minutes' => $listing->estimated_delivery_unit === 'HOUR'
                ? $listing->estimated_delivery_value * 60
                : $listing->estimated_delivery_value,
            'revision_no' => 1,
            'status' => AgreementStatus::DRAFT->value,
        ]);
    }

    /**
     * Create an immutable snapshot DTO from an agreement.
     */
    public function createSnapshot(Agreement $agreement): AgreementSnapshotDTO
    {
        return AgreementSnapshotDTO::fromModel($agreement);
    }

    /**
     * Calculate fee helper (kept for backwards compatibility).
     */
    public function calculateFee(float $basePrice, string $feeType = 'EXCLUDE'): FeeCalculationResultDTO
    {
        return $this->feeCalculationService->calculate($basePrice, $feeType);
    }

    /**
     * Propose a new draft agreement within a conversation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function proposeFromConversation(Conversation $conversation, User $proposer, array $data): Agreement
    {
        if (! $this->conversationService->isParticipant($conversation, $proposer)) {
            throw new AuthorizationException('Pengguna bukan merupakan peserta percakapan ini.');
        }

        if ($conversation->agreement_id) {
            $existing = Agreement::find($conversation->agreement_id);
            if ($existing) {
                if ($existing->status === AgreementStatus::FINALIZED || ! empty($existing->finalized_at)) {
                    throw new LogicException('Percakapan ini sudah memiliki kesepakatan yang berstatus FINALIZED.');
                }

                // If already has draft or rejected agreement, prompt user to revise
                throw new LogicException('Percakapan ini sudah memiliki lembar kesepakatan. Silakan gunakan fitur revisi usulan.');
            }
        }

        $otherUser = $this->conversationService->getOtherParticipant($conversation, $proposer);
        if (! $otherUser) {
            throw new InvalidArgumentException('Pihak lawan dalam percakapan tidak ditemukan.');
        }

        $role = strtoupper(trim((string) ($data['role'] ?? 'BUYER')));
        if ($role === 'BUYER') {
            $buyerId = $proposer->user_id;
            $sellerId = $otherUser->user_id;
            $buyerAcceptedAt = now();
            $sellerAcceptedAt = null;
        } else {
            $buyerId = $otherUser->user_id;
            $sellerId = $proposer->user_id;
            $buyerAcceptedAt = null;
            $sellerAcceptedAt = now();
        }

        $basePrice = (float) $data['base_price'];
        $feeType = strtoupper(trim((string) $data['fee_type']));
        $feeResult = $this->feeCalculationService->calculate($basePrice, $feeType);

        return DB::transaction(function () use (
            $conversation,
            $proposer,
            $otherUser,
            $buyerId,
            $sellerId,
            $buyerAcceptedAt,
            $sellerAcceptedAt,
            $basePrice,
            $feeResult,
            $data,
            $role
        ) {
            $agreement = Agreement::create([
                'created_by' => $proposer->user_id,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'variant_id' => (int) $data['variant_id'],
                'item_title' => trim((string) $data['item_title']),
                'item_description' => trim((string) $data['item_description']),
                'quantity' => (int) ($data['quantity'] ?? 1),
                'base_price' => $basePrice,
                'fee_type' => $feeResult->feeType->value,
                'fee_amount' => $feeResult->feeAmount,
                'buyer_total' => $feeResult->buyerTotal,
                'seller_net' => $feeResult->sellerNet,
                'delivery_method' => $data['delivery_method'],
                'delivery_within_minutes' => (int) $data['delivery_within_minutes'],
                'agreement_note' => isset($data['agreement_note']) ? trim((string) $data['agreement_note']) : null,
                'revision_no' => 1,
                'status' => AgreementStatus::DRAFT->value,
                'buyer_accepted_at' => $buyerAcceptedAt,
                'seller_accepted_at' => $sellerAcceptedAt,
            ]);

            $conversation->update(['agreement_id' => $agreement->agreement_id]);

            // Dispatch notification to opponent
            $this->notificationService->notifyProposal(
                $otherUser->user_id,
                null,
                $agreement->item_title,
                $proposer->username,
                false
            );

            // Send notification message in conversation thread
            $roleLabel = $role === 'BUYER' ? 'Pembeli' : 'Penjual';
            $feeModeLabel = $feeResult->feeType->value === 'EXCLUDE' ? 'EXCLUDE (ditanggung pembeli)' : 'INCLUDE (dipotong penjual)';
            $this->conversationService->sendMessage(
                $conversation,
                $proposer,
                sprintf(
                    "📄 Usulan Kesepakatan Transaksi (Rev #1) diajukan oleh @%s sebagai %s.\n\nItem: %s\nHarga Dasar: Rp%s | Mode: %s\nTotal Bayar Pembeli: Rp%s | Hak Bersih Penjual: Rp%s\nMetode Penyerahan: %s (Estimasi %d menit)\n\nMenunggu tinjauan dari @%s.",
                    $proposer->username,
                    $roleLabel,
                    $agreement->item_title,
                    number_format($basePrice, 0, ',', '.'),
                    $feeModeLabel,
                    number_format($feeResult->buyerTotal, 0, ',', '.'),
                    number_format($feeResult->sellerNet, 0, ',', '.'),
                    $agreement->deliveryMethodLabel(),
                    $agreement->delivery_within_minutes,
                    $otherUser->username
                )
            );

            return $agreement;
        });
    }

    /**
     * Revise an existing unfinalized agreement.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function reviseProposal(Agreement $agreement, User $reviser, array $data): Agreement
    {
        $isBuyer = ($agreement->buyer_id === $reviser->user_id);
        $isSeller = ($agreement->seller_id === $reviser->user_id);

        if (! $isBuyer && ! $isSeller) {
            throw new AuthorizationException('Pengguna bukan merupakan pihak dalam kesepakatan ini.');
        }

        return DB::transaction(function () use ($agreement, $reviser, $data, $isBuyer) {
            $lockedAgreement = Agreement::with(['buyer', 'seller', 'conversation'])
                ->where('agreement_id', $agreement->agreement_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAgreement->status === AgreementStatus::FINALIZED || ! empty($lockedAgreement->finalized_at)) {
                throw new LogicException('Kesepakatan sudah berstatus FINALIZED dan tidak dapat direvisi lagi.');
            }

            $recipientId = $isBuyer ? $lockedAgreement->seller_id : $lockedAgreement->buyer_id;

            $basePrice = (float) $data['base_price'];
            $feeType = strtoupper(trim((string) $data['fee_type']));
            $feeResult = $this->feeCalculationService->calculate($basePrice, $feeType);

            // Reviser accepts the new revision, opponent acceptance is invalidated
            $buyerAcceptedAt = $isBuyer ? now() : null;
            $sellerAcceptedAt = $isBuyer ? null : now();
            $newRevisionNo = $lockedAgreement->revision_no + 1;

            $lockedAgreement->update([
                'variant_id' => (int) $data['variant_id'],
                'item_title' => trim((string) $data['item_title']),
                'item_description' => trim((string) $data['item_description']),
                'quantity' => (int) ($data['quantity'] ?? 1),
                'base_price' => $basePrice,
                'fee_type' => $feeResult->feeType->value,
                'fee_amount' => $feeResult->feeAmount,
                'buyer_total' => $feeResult->buyerTotal,
                'seller_net' => $feeResult->sellerNet,
                'delivery_method' => $data['delivery_method'],
                'delivery_within_minutes' => (int) $data['delivery_within_minutes'],
                'agreement_note' => isset($data['agreement_note']) ? trim((string) $data['agreement_note']) : null,
                'revision_no' => $newRevisionNo,
                'status' => AgreementStatus::DRAFT->value,
                'buyer_accepted_at' => $buyerAcceptedAt,
                'seller_accepted_at' => $sellerAcceptedAt,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            // Notify recipient about revision
            $this->notificationService->notifyProposal(
                $recipientId,
                null,
                $lockedAgreement->item_title,
                $reviser->username,
                true // isRevision
            );

            // Send notification message in conversation thread
            $conversation = $lockedAgreement->conversation;
            if ($conversation) {
                $feeModeLabel = $feeResult->feeType->value === 'EXCLUDE' ? 'EXCLUDE (ditanggung pembeli)' : 'INCLUDE (dipotong penjual)';
                $this->conversationService->sendMessage(
                    $conversation,
                    $reviser,
                    sprintf(
                        "📝 Lembar kesepakatan telah DIREVISI (Rev #%d) oleh @%s.\n\nHarga Dasar: Rp%s | Mode: %s\nTotal Bayar Pembeli: Rp%s | Hak Bersih Penjual: Rp%s\nPersetujuan atas revisi sebelumnya telah dibatalkan.",
                        $newRevisionNo,
                        $reviser->username,
                        number_format($basePrice, 0, ',', '.'),
                        $feeModeLabel,
                        number_format($feeResult->buyerTotal, 0, ',', '.'),
                        number_format($feeResult->sellerNet, 0, ',', '.')
                    )
                );
            }

            return $lockedAgreement;
        });
    }

    /**
     * Accept a matching revision of an agreement, locking snapshot and creating a Transaction.
     *
     * @throws AuthorizationException
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function acceptProposal(Agreement $agreement, User $acceptor, int $revisionNo): Transaction
    {
        return DB::transaction(function () use ($agreement, $acceptor, $revisionNo) {
            $lockedAgreement = Agreement::with(['buyer', 'seller', 'conversation'])
                ->where('agreement_id', $agreement->agreement_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: if already finalized and transaction exists, return existing
            if ($lockedAgreement->status === AgreementStatus::FINALIZED || ! empty($lockedAgreement->finalized_at)) {
                $existingTx = Transaction::where('agreement_id', $lockedAgreement->agreement_id)->first();
                if ($existingTx) {
                    return $existingTx;
                }
            }

            if ($lockedAgreement->status === AgreementStatus::REJECTED) {
                throw new LogicException('Usulan kesepakatan ini telah ditolak. Silakan ajukan revisi sebelum menyetujui.');
            }

            if ($revisionNo !== $lockedAgreement->revision_no) {
                throw new InvalidArgumentException(sprintf(
                    'Revisi usulan kesepakatan telah diperbarui ke revisi #%d. Anda tidak dapat menyetujui revisi usang (#%d).',
                    $lockedAgreement->revision_no,
                    $revisionNo
                ));
            }

            $isBuyer = ($acceptor->user_id === $lockedAgreement->buyer_id);
            $isSeller = ($acceptor->user_id === $lockedAgreement->seller_id);

            if (! $isBuyer && ! $isSeller) {
                throw new AuthorizationException('Anda tidak memiliki hak untuk menyetujui kesepakatan ini.');
            }

            // Proposer cannot accept their own unreviewed proposal
            if ($isBuyer && $lockedAgreement->buyer_accepted_at !== null && $lockedAgreement->seller_accepted_at === null) {
                throw new LogicException('Anda adalah pihak yang mengajukan revisi ini. Menunggu persetujuan dari pihak lawan.');
            }

            if ($isSeller && $lockedAgreement->seller_accepted_at !== null && $lockedAgreement->buyer_accepted_at === null) {
                throw new LogicException('Anda adalah pihak yang mengajukan revisi ini. Menunggu persetujuan dari pihak lawan.');
            }

            $now = now();
            if ($isBuyer) {
                $lockedAgreement->buyer_accepted_at = $now;
            } else {
                $lockedAgreement->seller_accepted_at = $now;
            }

            $lockedAgreement->status = AgreementStatus::FINALIZED->value;
            $lockedAgreement->finalized_at = $now;
            $lockedAgreement->save();

            // Ensure exactly one transaction is created
            $transaction = Transaction::where('agreement_id', $lockedAgreement->agreement_id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                $transaction = Transaction::create([
                    'agreement_id' => $lockedAgreement->agreement_id,
                    'source_type' => TransactionSourceType::FORUM->value,
                    'post_id' => $lockedAgreement->conversation?->post_id,
                    'listing_id' => null,
                    'status' => TransactionStatus::WAITING_PAYMENT->value,
                    'payment_due_at' => $now->copy()->addHour(),
                    'delivery_due_at' => null,
                    'confirmation_due_at' => null,
                ]);

                TransactionStatusHistory::create([
                    'transaction_id' => $transaction->transaction_id,
                    'from_status' => null,
                    'to_status' => TransactionStatus::WAITING_PAYMENT->value,
                    'notes' => 'Transaksi resmi dibentuk setelah lembar kesepakatan Forum/DM disetujui kedua belah pihak.',
                    'created_at' => $now,
                ]);
            }

            // Send notification to both parties
            $this->notificationService->notify(
                $lockedAgreement->buyer_id,
                $transaction->transaction_id,
                NotificationEventType::TRANSACTION_CREATED,
                sprintf(
                    "Kesepakatan final '%s' telah disetujui! Transaksi #%d dibentuk. Silakan bayar dalam 1 jam.",
                    mb_substr($lockedAgreement->item_title, 0, 40),
                    $transaction->transaction_id
                )
            );

            $this->notificationService->notify(
                $lockedAgreement->seller_id,
                $transaction->transaction_id,
                NotificationEventType::TRANSACTION_CREATED,
                sprintf(
                    "Kesepakatan final '%s' telah disetujui! Transaksi #%d menunggu pembayaran dari pembeli.",
                    mb_substr($lockedAgreement->item_title, 0, 40),
                    $transaction->transaction_id
                )
            );

            // Post confirmation message to conversation thread
            $conversation = $lockedAgreement->conversation;
            if ($conversation) {
                $this->conversationService->sendMessage(
                    $conversation,
                    $acceptor,
                    sprintf(
                        "🤝 Kesepakatan (Rev #%d) resmi DISETUJUI oleh @%s!\nTransaksi #%d telah dibentuk dengan status Menunggu Pembayaran (Tenggat: 1 Jam).\nSilakan lanjutkan pembayaran melalui sistem escrow.",
                        $lockedAgreement->revision_no,
                        $acceptor->username,
                        $transaction->transaction_id
                    )
                );
            }

            return $transaction;
        });
    }

    /**
     * Reject an agreement with reason.
     *
     * @throws AuthorizationException
     * @throws LogicException
     */
    public function rejectProposal(Agreement $agreement, User $rejector, string $reason): Agreement
    {
        return DB::transaction(function () use ($agreement, $rejector, $reason) {
            $lockedAgreement = Agreement::with(['buyer', 'seller', 'conversation'])
                ->where('agreement_id', $agreement->agreement_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAgreement->status === AgreementStatus::FINALIZED || ! empty($lockedAgreement->finalized_at)) {
                throw new LogicException('Kesepakatan yang sudah berstatus FINALIZED tidak dapat ditolak.');
            }

            $isBuyer = ($rejector->user_id === $lockedAgreement->buyer_id);
            $isSeller = ($rejector->user_id === $lockedAgreement->seller_id);

            if (! $isBuyer && ! $isSeller) {
                throw new AuthorizationException('Anda tidak memiliki hak untuk menolak kesepakatan ini.');
            }

            $lockedAgreement->status = AgreementStatus::REJECTED->value;
            $lockedAgreement->rejected_by = $rejector->user_id;
            $lockedAgreement->rejected_at = now();
            $lockedAgreement->rejection_reason = trim($reason);
            $lockedAgreement->save();

            // Post rejection message to conversation thread
            $conversation = $lockedAgreement->conversation;
            if ($conversation) {
                $this->conversationService->sendMessage(
                    $conversation,
                    $rejector,
                    sprintf(
                        "❌ Usulan kesepakatan (Rev #%d) DITOLAK oleh @%s.\nAlasan Penolakan: %s\nAnda dapat mengajukan revisi baru bila ingin menegosiasikan ulang.",
                        $lockedAgreement->revision_no,
                        $rejector->username,
                        $lockedAgreement->rejection_reason
                    )
                );
            }

            return $lockedAgreement;
        });
    }
}
