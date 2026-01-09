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

    // public function __construct($name, $userCode, $temp)
    // {
    //     $this->name = $name;
    //     $this->userCode = $userCode;
    //     $this->temp = $temp;

    //     // Build full frontend URL for Change Password page
    //     $frontend = rtrim(config('app.frontend_url'), '/');
    //     $this->resetUrl = $frontend . '/change-password?user=' . urlencode($userCode);
    // }

    public function __construct(
    string $purpose, string $name, string $userCode, ?string $temp = null
) {
    $this->purpose  = $purpose;
    $this->name     = $name;
    $this->userCode = $userCode;
    $this->temp     = $temp; // null for reset
}


    // public function build()
    // {
    //     return $this->subject('Your Temporary Password')
    //                 ->view('emails.temp_password')
    //                 ->with([
    //                     'name'      => $this->name,
    //                     'userCode'  => $this->userCode,
    //                     'temp'      => $this->temp,
    //                     'resetUrl'  => $this->resetUrl, // 👈 pass this explicitly
    //                 ]);
    // }


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
                ]);
}
}
