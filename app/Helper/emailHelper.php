<?php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;

    if (! function_exists('signup_email')){
        function signup_email($user){
            Mail::send('sample_email', [], function($message) use($user) {
                $message->to($user->email, $user->first_name)->subject($user->first_name .' - 🎉 Welcome to SnapRytr – Let’s Get Started!');
            });
        }
    }
    if (! function_exists('signup_email_test')){
        function signup_email_test(){
            Mail::send('sample_email', [], function($message) {
                $message->to(['umerrasheed3@gmail.com', 'hello@snaprytr.com'], 'Umer Rasheed')->subject('Umer Rasheed - 🎉 Welcome to SnapRytr – Let’s Get Started!');
            });
        }
    }
