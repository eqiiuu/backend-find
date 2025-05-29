<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Resend\Laravel\Facades\Resend;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        try {
            Log::info('Password reset request received', [
                'email' => $request->email
            ]);

            $validator = validator($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'Please enter your email address',
                'email.email' => 'Please enter a valid email address',
                'email.exists' => 'This email address is not registered in our system'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'status' => 'error'
                ], 422);
            }

            $email = $request->email;

            // Generate both token and OTP
            $token = Str::random(64);
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Clear any existing tokens for this email
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Insert new token and OTP
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => $token,
                'otp' => $otp,
                'created_at' => Carbon::now()
            ]);

            // Send email using Resend
            try {
                $emailHtml = view('emails.reset-password', ['otp' => $otp])->render();
                
                // Check if Resend is properly configured
                if (empty(config('resend.api_key'))) {
                    Log::error('Resend API key is not configured', [
                        'config' => [
                            'resend_api_key_exists' => !empty(config('resend.api_key')),
                            'mail_from_address' => config('mail.from.address'),
                            'mail_mailer' => config('mail.default'),
                        ]
                    ]);
                    throw new \Exception('Resend API key is not configured');
                }

                if (empty(config('mail.from.address'))) {
                    Log::error('From email address is not configured', [
                        'config' => [
                            'mail_from_address' => config('mail.from.address'),
                            'mail_from_name' => config('mail.from.name'),
                        ]
                    ]);
                    throw new \Exception('From email address is not configured');
                }

                Log::info('Attempting to send email', [
                    'from' => config('mail.from.address'),
                    'to' => $email,
                    'subject' => 'Reset Password OTP Code'
                ]);

                $result = Resend::emails()->send([
                    'from' => config('mail.from.address'),
                    'to' => $email,
                    'subject' => 'Reset Password OTP Code',
                    'html' => $emailHtml
                ]);

                if (empty($result)) {
                    Log::error('Empty response from Resend API');
                    throw new \Exception('No response from Resend API');
                }

                Log::info('Reset email sent successfully', [
                    'email' => $email,
                    'result' => $result
                ]);

                return response()->json([
                    'message' => 'OTP code sent to your email',
                    'status' => 'success'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send reset email', [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                    'config' => [
                        'resend_api_key_exists' => !empty(config('resend.api_key')),
                        'from_address' => config('mail.from.address'),
                        'mail_driver' => config('mail.default'),
                        'resend_transport' => config('mail.mailers.resend.transport')
                    ]
                ]);

                return response()->json([
                    'message' => 'Failed to send reset email: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in password reset process', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);

            $statusCode = 500;
            $message = 'Failed to send reset email. Please try again later.';

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $statusCode = 422;
                $message = $e->errors();
            }

            return response()->json([
                'message' => $message,
                'error' => $e->getMessage(),
                'status' => 'error'
            ], $statusCode);
        }
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('otp', $request->token)
            ->orWhere('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 400);
        }

        // Check if code is expired (60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $resetRecord->email)->delete();
            return response()->json([
                'message' => 'Verification code has expired'
            ], 400);
        }

        return response()->json([
            'message' => 'Verification code is valid',
            'email' => $resetRecord->email
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('otp', $request->token)
            ->orWhere('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 400);
        }

        $user = User::where('email', $resetRecord->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'message' => 'Password has been successfully reset'
        ]);
    }
} 