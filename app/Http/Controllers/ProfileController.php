<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function __invoke(): Response
    {
        return Inertia::render('profile');
    }
}
