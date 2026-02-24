<?php

namespace App\Controllers;

use App\Core\Controller;

final class PageController extends Controller
{
    public function about(): void
    {
        $this->view('page/about', [
            'pageTitle' => 'Hakkımızda | BSC Elektronik',
        ]);
    }

    public function contact(): void
    {
        $this->view('page/contact', [
            'pageTitle' => 'İletişim | BSC Elektronik',
        ]);
    }
}
