<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if( !function_exists('verifySubMinutes') ) {
    /**
     * @return \Illuminate\Support\Carbon
     */
    function verifySubMinutes(): Carbon
    {
        return Carbon::now()->subMinutes(Config::get('mail.verification.expire', 60));
    }
}
if( !function_exists('mailer') ) {
    /**
     * @param array|string $to
     * @param string       $title
     * @param string       $message
     *
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    function mailer(
        string $title,
        string|Closure $message,
        array|string $to = [ 'email' => null, 'name' => null ],
        array $data = [ 'hash' => null, 'name' => null ],
    ): bool {
        $to = is_string($to) ? [ 'email' => $to ] : $to;
        if( !isset($to[ 'email' ]) && isset($to[ 0 ]) ) {
            foreach( $to as &$_email ) {
                if( !is_array($_email) ) {
                    $_email = [ 'email' => $_email ];
                }
            }
            unset($_email);
        }

        $default_mailler = Config::get('mail.default', 'smtp');
        $mailer = Config::get("mail.mailers.{$default_mailler}");

        $isSmtp = $mailer[ 'transport' ] === 'smtp';
        $mail = new PHPMailer();
        if( $isSmtp ) {
            $mail->IsSMTP();
        }
        $mail->Mailer = $mailer[ 'transport' ];

        $mail->SMTPDebug = $mailer[ 'debug' ] ? 1 : 0;
        $mail->SMTPAuth = $mailer[ 'encryption' ] ? TRUE : FALSE;
        $mail->SMTPSecure = $mailer[ 'encryption' ];
        $mail->Port = $mailer[ 'port' ];
        $mail->Host = $mailer[ 'host' ];
        $mail->Username = $mailer[ 'username' ];
        $mail->Password = $mailer[ 'password' ];

        $mail->IsHTML(true);
        $name = $data[ 'name' ] ?? null;
        foreach( $to as $value ) {
            $value = array_wrap($value);
            $mail->AddAddress($_email = $value[ 'email' ] ?? $value[ 0 ], $_name = $value[ 'name' ] ?? null);
            $name ??= $_name ?? $_email;
        }
        $mail->CharSet = $mail::CHARSET_UTF8;
        $mail->SetFrom(config('mail.from.address'), config('mail.from.name'));
        // $mail->AddReplyTo("reply-to-email@domain", "reply-to-name");
        // $mail->AddCC("cc-recipient-email@domain", "cc-recipient-name");
        // $title = iconv('Windows-1256', 'UTF-8//TRANSLIT', $title);
        $mail->Subject = $title;
        // $content = iconv('Windows-1256', 'UTF-8//TRANSLIT', $message);

        if( $message instanceof Closure ) {
            $data[ 'name' ] = $name;
            $content = $message($data);
        }

        $mail->MsgHTML($content);
        try {
            if( !$mail->Send() ) {
                if( request()->has('s-r') ) {
                    dd(__LINE__, $mail->ErrorInfo);
                }

                return false;
            }
        } catch(Exception $exception) {
            if( request()->has('s-r') ) {
                dd(__LINE__, $exception);
            }
        }

        return true;
    }
}