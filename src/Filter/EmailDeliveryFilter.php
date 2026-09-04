<?php

namespace Lexio\AdminBundle\Filter;

use Lexio\AdminBundle\Filter\BaseFilter;

class EmailDeliveryFilter extends BaseFilter
{
    public ?string $recipientEmail = null;
    public ?string $senderEmail = null;
    public ?string $error = null;
}
