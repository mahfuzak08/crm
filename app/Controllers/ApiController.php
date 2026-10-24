<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Query;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\LeadService;
use App\Services\Tenant;

/** Small JSON endpoints for AJAX lookups (session-authenticated, company-scoped). */
final class ApiController extends Controller
{
    public function customers(): never
    {
        if (!can('customers.view') && !can('sales.create') && !can('invoices.create') && !can('offers.create')) {
            $this->authorize('customers.view');
        }
        $q = Request::str('q');
        Response::json(['results' => mb_strlen($q) >= 2 ? CustomerService::lookup($q) : []]);
    }

    public function leads(): never
    {
        $this->authorize('leads.view');
        $q = Request::str('q');
        Response::json(['results' => mb_strlen($q) >= 2 ? LeadService::lookup($q) : []]);
    }

    public function openInvoices(): never
    {
        $this->authorize('payments.create');
        Response::json(['results' => InvoiceService::openForCustomer(Request::int('customer') ?: null)]);
    }

    /** Global search (top bar). */
    public function search(): never
    {
        $term = Request::str('q');
        $results = [];
        if (mb_strlen($term) >= 2) {
            if (can('leads.view')) {
                foreach (LeadService::lookup($term) as $r) {
                    $results[] = ['type' => 'Lead', 'text' => $r['text'], 'url' => url('/leads/' . $r['id'])];
                }
            }
            if (can('customers.view')) {
                foreach (CustomerService::lookup($term) as $r) {
                    $results[] = ['type' => 'Customer', 'text' => $r['text'], 'url' => url('/customers/' . $r['id'])];
                }
            }
            if (can('invoices.view')) {
                $rows = Query::table('invoices i')->select('i.id, i.invoice_number, c.name')->join('JOIN customers c ON c.id = i.customer_id')
                    ->where('i.company_id = ?', Tenant::id())->where('i.deleted_at IS NULL')->search(['i.invoice_number'], $term)->orderBy('i.id DESC')->get(10);
                foreach ($rows as $r) {
                    $results[] = ['type' => 'Invoice', 'text' => $r['invoice_number'] . ' — ' . $r['name'], 'url' => url('/invoices/' . $r['id'])];
                }
            }
            if (can('payments.view')) {
                $rows = Query::table('payments p')->select('p.id, p.payment_number, p.reference')->where('p.company_id = ?', Tenant::id())
                    ->search(['p.payment_number', 'p.reference'], $term)->orderBy('p.id DESC')->get(10);
                foreach ($rows as $r) {
                    $results[] = ['type' => 'Payment', 'text' => $r['payment_number'] . ($r['reference'] ? ' — ' . $r['reference'] : ''), 'url' => url('/payments/' . $r['id'])];
                }
            }
        }
        if (Request::isAjax()) {
            Response::json(['results' => $results]);
        }
        render('search/index', ['title' => 'Search', 'breadcrumbs' => ['Search' => null], 'q' => $term, 'results' => $results]);
    }
}
