<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TutorialController extends Controller
{
    public function index(): View
    {
        return view('tutorial.index');
    }
}
