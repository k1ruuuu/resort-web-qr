<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(auth()->check(), 403);

        return view('docs.index');
    }
}
