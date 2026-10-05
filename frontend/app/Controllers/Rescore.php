<?php

namespace App\Controllers;

/**
 * Standalone rescore menu removed — scoring ulang is triggered from a finished transaction
 * via POST /transactions/{id}/duplicate (same debtor/product, new transaction id).
 */
class Rescore extends BaseController
{
    public function index()
    {
        return redirect()->to('/transactions');
    }

    public function create()
    {
        return redirect()->to('/transactions')->with('error', 'Scoring ulang dilakukan dari daftar/detail transaksi yang sudah selesai.');
    }

    public function approve(int $id)
    {
        return redirect()->to('/transactions');
    }
}
