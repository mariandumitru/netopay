<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

class StartConfigDto
{
    public string $notifyUrl;
    public string $redirectUrl;
    public string $language;
    public string $emailTemplate;

    public function __construct()
    {
        $this->notifyUrl     = config('netopay.notify_url') ?: route('netopia.ipn');
        $this->redirectUrl   = config('netopay.redirect_url') ?: route('netopia.return');
        $this->language      = config('netopay.language', 'ro');
        $this->emailTemplate = config('netopay.email_template', 'confirm');
    }

    public function toArray(): array
    {
        return [
            'notifyUrl'     => $this->notifyUrl,
            'redirectUrl'   => $this->redirectUrl,
            'language'      => $this->language,
            'emailTemplate' => $this->emailTemplate,
        ];
    }
}
