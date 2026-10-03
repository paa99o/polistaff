<?php

namespace App\Services;

use App\Models\EmailDelivery;
use App\Models\PortalNotification;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class FinancialHistoryDeletionService
{
    public function deleteRelatedMessages(Model $record, string $pathSuffix): void
    {
        EmailDelivery::query()
            ->where('record_type', $record::class)
            ->where('record_id', $record->getKey())
            ->delete();

        PortalNotification::query()
            ->where('link', 'like', '%'.$pathSuffix)
            ->delete();
    }

    public function deleteTransactionAndReversals(?Transaction $transaction): void
    {
        if (! $transaction) {
            return;
        }

        Transaction::query()
            ->where('reversal_of_id', $transaction->id)
            ->get()
            ->each(fn (Transaction $reversal) => $this->deleteTransactionAndReversals($reversal));

        $transaction->delete();
    }
}
