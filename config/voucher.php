<?php

return [
    'qr_rate_limit' => (int) env('VOUCHER_QR_RATE_LIMIT', 30),
    'redeem_rate_limit' => (int) env('VOUCHER_REDEEM_RATE_LIMIT', 10),
];
