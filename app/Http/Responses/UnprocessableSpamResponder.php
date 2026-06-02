<?php

namespace App\Http\Responses;

use Closure;
use Illuminate\Http\Request;
use Spatie\Honeypot\SpamResponder\SpamResponder;

class UnprocessableSpamResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next)
    {
        return response()->json(['message' => 'Invalid submission.'], 422);
    }
}
