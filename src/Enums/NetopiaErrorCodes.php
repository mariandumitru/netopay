<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

enum NetopiaErrorCodes: int
{
    case SetAuthenticationToken       = 100;
    case DuplicatedOrderID            = 56;
    case OtherOrderWithDifferentPrice = 99;
    case ExpireCardError              = 19;
    case FundsError                   = 20;
    case CVVError                     = 21;
    case CVVError2                    = 22;
    case ForbiddenCardTransaction     = 34;
    case CardNo3DS                    = 0;
}
