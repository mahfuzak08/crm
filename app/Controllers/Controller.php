<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

abstract class Controller
{
    protected function authorize(string $permission): void
    {
        AuthService::authorize($permission);
    }

    /** Render page; for AJAX list refreshes render only the given partial. */
    protected function view(string $view, array $data = [], ?string $ajaxPartial = null): never
    {
        if ($ajaxPartial !== null && Request::isAjax()) {
            Response::html(partial($ajaxPartial, $data));
        }
        render($view, $data);
    }

    protected function success(string $message, string $redirect): never
    {
        if (Request::isAjax()) {
            Response::json(['ok' => true, 'message' => $message, 'redirect' => url($redirect)]);
        }
        flash('success', $message);
        Response::redirect($redirect);
    }

    protected function page(): int
    {
        return max(1, Request::int('page', 1));
    }
}
