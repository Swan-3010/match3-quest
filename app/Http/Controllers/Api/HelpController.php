<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use App\Services\Game\EconomyConfig;

class HelpController extends Controller
{
    const PRICE_CODES = [
        'hammer_price' => 'hammer',
        'row_price' => 'meteor_row',
        'col_price' => 'meteor_col',
        'cross_price' => 'cross_diag',
        'shuffle_price' => 'shuffle',
        'life_1_price' => 'life_1',
        'lives_refill_price' => 'lives_refill',
    ];

    public function index()
    {
        $items = ShopItem::whereIn('code', array_values(self::PRICE_CODES))->get()->keyBy('code');

        $replacements = [
            ':gem_rate:' => (string) EconomyConfig::get('gem_exchange_rate', 1000),
            ':regen_minutes:' => (string) EconomyConfig::get('regen_minutes', 180),
        ];
        foreach (self::PRICE_CODES as $placeholder => $code) {
            $replacements[':'.$placeholder.':'] = $this->formatPrice($items->get($code));
        }

        $sections = [];
        foreach ((array) trans('help', [], 'ru') as $id => $section) {
            $sections[] = [
                'id' => $id,
                'title' => $section['title'],
                'html' => strtr($section['html'], $replacements),
            ];
        }

        return response()->json(['sections' => $sections]);
    }

    private function formatPrice(?ShopItem $item): string
    {
        if (!$item) return '—';
        $currency = $item->price_currency
            ?? ($item->price_coins === null && $item->price_gems !== null ? 'gems' : 'coins');
        $price = $currency === 'gems' ? $item->price_gems : $item->price_coins;
        if ($price === null) return '—';
        return $currency === 'gems' ? "{$price} 💎" : "{$price} 🪙";
    }
}
