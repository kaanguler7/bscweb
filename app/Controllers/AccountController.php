<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Order;

final class AccountController extends Controller
{
    public function orders(): void
    {
        $user = UserAuth::user();
        if ($user === null) {
            $this->flash('danger', 'Siparişlerinizi görmek için giriş yapmalısınız.');
            $this->redirect('auth/login');
        }

        $orders = Order::forUser((int) $user['id']);

        $this->view('account/orders', [
            'pageTitle' => 'Siparişlerim | BSC Elektronik',
            'orders' => $orders,
            'user' => $user,
        ]);
    }
}
