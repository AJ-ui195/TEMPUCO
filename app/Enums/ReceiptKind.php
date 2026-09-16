<?php

namespace App\Enums;

enum ReceiptKind: string
{
    case OfficialReceipt = 'official_receipt';
    case Invoice = 'invoice';
}
