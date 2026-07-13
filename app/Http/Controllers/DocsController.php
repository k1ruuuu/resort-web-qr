<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function __invoke(): View
    {
        return view('docs.index');
    }
}
