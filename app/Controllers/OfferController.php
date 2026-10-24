<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;
use App\Services\LeadService;
use App\Services\NotificationService;
use App\Services\OfferService;
use App\Services\ProjectService;
use App\Services\SettingsService;
use App\Services\ShareLinkService;

final class OfferController extends Controller
{
    public function index(): never
    {
        $this->authorize('offers.view');
        $f = Request::filters(['q', 'status', 'project', 'from', 'to', 'sort', 'dir']);
        $this->view('offers/index', [
            'title' => 'Offers', 'breadcrumbs' => ['CRM' => null, 'Offers' => null], 'f' => $f,
            'p' => OfferService::query($f)->paginate($this->page()), 'projects' => ProjectService::options(),
        ], 'offers/_list');
    }

    public function create(): never
    {
        $this->authorize('offers.create');
        $prefill = ['valid_until' => date('Y-m-d', strtotime('+' . (int) SettingsService::get('offer.valid_days', 15) . ' days')), 'quantity' => 1];
        if ($lid = Request::int('lead')) {
            $lead = LeadService::find($lid);
            $prefill += ['lead_id' => $lid, 'project_id' => $lead['project_id']];
            if ($lead['customer_id']) {
                $prefill['customer_id'] = $lead['customer_id'];
            }
        }
        if ($cid = Request::int('customer')) {
            CustomerService::find($cid);
            $prefill['customer_id'] = $cid;
        }
        $this->form(null, $prefill);
    }

    public function store(): never
    {
        $this->authorize('offers.create');
        $id = OfferService::save(Request::post());
        $this->success('Offer created.', '/offers/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('offers.view');
        $o = OfferService::find($id);
        $phone = $o['customer_phone'] ?? $o['lead_phone'];
        $link = in_array($o['status'], ['draft', 'sent', 'viewed', 'accepted'], true) ? ShareLinkService::url('offer', $id, 30) : null;
        render('offers/show', [
            'title' => 'Offer ' . $o['offer_number'], 'breadcrumbs' => ['Offers' => '/offers', $o['offer_number'] => null],
            'o' => $o, 'publicLink' => $link,
            'whatsapp' => $link ? NotificationService::whatsappLink($phone, 'Dear ' . ($o['customer_name'] ?? $o['lead_name']) . ', please view your share offer ' . $o['offer_number'] . ': ' . $link) : null,
            'canEmail' => NotificationService::canEmail() && ($o['customer_email'] ?? $o['lead_email']),
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('offers.edit');
        $this->form(OfferService::find($id), []);
    }

    public function update(int $id): never
    {
        $this->authorize('offers.edit');
        OfferService::save(Request::post(), $id);
        $this->success('Offer updated.', '/offers/' . $id);
    }

    public function send(int $id): never
    {
        $this->authorize('offers.send');
        [$emailed] = OfferService::send($id);
        $this->success($emailed ? 'Offer emailed and marked as sent.' : 'Offer marked as sent. Share the link via WhatsApp or copy it.', '/offers/' . $id);
    }

    public function status(int $id): never
    {
        $this->authorize('offers.edit');
        OfferService::setStatus($id, Request::str('status'));
        $this->success('Offer status updated.', '/offers/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('offers.delete');
        OfferService::delete($id);
        $this->success('Offer deleted.', '/offers');
    }

    public function print(int $id): never
    {
        $this->authorize('offers.view');
        $o = OfferService::find($id);
        render('offers/print', OfferService::printData($o) + ['docTitle' => 'Offer'], 'layouts/print');
    }

    /** Accepted offer -> prefilled share sale (customer required; lead converted first). */
    public function convert(int $id): never
    {
        $this->authorize('sales.create');
        $o = OfferService::find($id);
        if (!$o['customer_id'] && $o['lead_id']) {
            $this->authorize('leads.convert');
            $cid = LeadService::convert((int) $o['lead_id']);
            Database::update('offers', ['customer_id' => $cid], 'id = ?', [$id]);
        }
        Response::redirect('/sales/create?offer=' . $id);
    }

    private function form(?array $offer, array $prefill): never
    {
        $rec = $offer ?? $prefill;
        render('offers/form', [
            'title' => $offer ? 'Edit Offer ' . $offer['offer_number'] : 'New Offer',
            'breadcrumbs' => ['Offers' => '/offers', ($offer ? $offer['offer_number'] : 'New') => null],
            'offer' => $offer, 'rec' => $rec,
            'projects' => ProjectService::options(),
            'customers' => CustomerService::options(isset($rec['customer_id']) ? (int) $rec['customer_id'] : null),
            'leads' => LeadService::options(),
            'feeOverride' => SettingsService::get('sales.allow_fee_override') === '1',
        ]);
    }
}
