<?php

namespace App\Controllers;

use App\Core\Controller;

final class PageController extends Controller
{
    public function about(): void
    {
        $this->view('page/about', [
            'pageTitle' => 'Hakkımızda | BSC Elektronik',
            'metaDescription' => 'BSC Elektronik, televizyon yedek parçalarında ürün kodu ve model uyumluluğuna odaklanan uzman tedarikçidir.',
            'canonicalUrl' => $this->absoluteRouteUrl('about'),
        ]);
    }

    public function contact(): void
    {
        $this->view('page/contact', [
            'pageTitle' => 'İletişim | BSC Elektronik',
            'metaDescription' => 'TV yedek parça, ürün kodu ve sipariş sorularınız için BSC Elektronik ile iletişime geçin.',
            'canonicalUrl' => $this->absoluteRouteUrl('contact'),
        ]);
    }
}
