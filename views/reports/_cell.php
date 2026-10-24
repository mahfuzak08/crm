<?php
/** vars: $value, $kind, optional $company (print) */
switch ($kind) {
    case 'money': echo isset($company) ? money_c($value, $company) : money($value); break;
    case 'int': echo number_format((int) $value); break;
    case 'date': echo e(fdate((string) $value)); break;
    case 'status': echo isset($company) ? e(ucfirst((string) $value)) : status_badge((string) $value); break;
    default: echo e(mb_strimwidth((string) $value, 0, 120, '…'));
}
