<?php

namespace App\Http\Controllers;

use App\Services\Vittles\CatalogService;
use App\Services\Vittles\OrderService;
use App\Services\Vittles\VittlesException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use InvalidArgumentException;

class VittlesController extends Controller
{
    public function index(CatalogService $catalog): View
    {
        return view('vittles.order', $this->catalogData($catalog));
    }

    public function locations(CatalogService $catalog): View
    {
        return view('vittles.locations', $this->catalogData($catalog));
    }

    public function place(Request $request, OrderService $orders): RedirectResponse
    {
        $input = $request->validate([
            'location' => ['required', 'string', 'max:80'],
            'item' => ['required', 'string', 'max:200'],
        ]);

        try {
            $result = $orders->place($input['location'], $input['item']);
        } catch (InvalidArgumentException|VittlesException $e) {
            return back()->withInput()->with('operation_error', $e->getMessage());
        }

        $request->session()->put('vittles_last_result', $result);

        return redirect()->route('vittles.result');
    }

    public function result(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('vittles_last_result');
        if (! is_array($result)) {
            return redirect()->route('vittles.order');
        }

        return view('vittles.result', ['result' => $result]);
    }

    private function catalogData(CatalogService $catalog): array
    {
        try {
            return [
                'catalog' => Cache::store('file')->remember('vittles-web-catalog', 10, fn () => $catalog->load()),
                'catalogError' => null,
            ];
        } catch (VittlesException $e) {
            return ['catalog' => ['locations' => [], 'menus' => []], 'catalogError' => $e->getMessage()];
        }
    }
}
