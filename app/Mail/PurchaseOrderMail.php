<?php

namespace App\Mail;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Purchase $purchase;
    public ?string $note;

    public function __construct(Purchase $purchase, ?string $note = null)
    {
        $this->purchase = $purchase->loadMissing(['supplier','items.product']);
        $this->note = $note;
        $this->subject('Purchase Order #'.$purchase->id.' - '.$purchase->supplier->name);
    }

    public function build()
    {
        return $this->markdown('emails.purchases.order', [
            'purchase' => $this->purchase,
            'note'     => $this->note,
        ]);
    }
}
