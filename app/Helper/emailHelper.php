<?php


    use Illuminate\Support\Facades\Mail;

    if (! function_exists('signup_email')){
        function signup_email(){
            $user = \Illuminate\Support\Facades\Auth::user();

            Mail::send('sample_email', [], function($message, $user) {
                $message->to($user->email, $user->first_name)->subject('Signup Successfully');
            });
        }
    }
