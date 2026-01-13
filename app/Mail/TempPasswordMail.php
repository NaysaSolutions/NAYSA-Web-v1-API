<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TempPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $purpose;   // 'release' | 'reset'
    public string $name;
    public string $userCode;
    public ?string $temp;     // used only for 'release'
    public ?string $company;  // ✅ tenant / company code

    public function __construct(
        string $purpose,
        string $name,
        string $userCode,
        ?string $temp = null,
        ?string $company = null
    ) {
        $this->purpose  = $purpose;
        $this->name     = $name;
        $this->userCode = $userCode;
        $this->temp     = $temp;     // null for reset
        $this->company  = $company;  // ✅ REQUIRED
    }

    public function build()
    {
        $subject = $this->purpose === 'reset'
            ? 'Password Reset Request'
            : 'Your Account Has Been Released';

        return $this->subject($subject)
            ->view('emails.temp_password')
            ->with([
                'purpose'  => $this->purpose,
                'name'     => $this->name,
                'userCode' => $this->userCode,
                'temp'     => $this->temp,
                'company'  => $this->company, // ✅ THIS ENABLES &company=
            ]);
    }
}
