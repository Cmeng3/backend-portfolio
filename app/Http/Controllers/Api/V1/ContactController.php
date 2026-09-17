<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    public function __invoke(ContactRequest $request): mixed
    {
        ContactMessage::create($request->safe()->only(['name', 'email', 'subject', 'message']));

        return response()->json(['message' => 'Thank you. Your message has been received.'], 201);
    }
}
